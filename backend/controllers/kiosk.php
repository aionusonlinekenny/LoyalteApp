<?php
// POST /api/kiosk/claim   Customer self-service earn-points (no auth required)

if ($method !== 'POST' || $id !== 'claim') {
    json_error('Not found', 404);
}

$db    = get_db();
$body  = json_body();
$nowMs = (int)(microtime(true) * 1000);

// ── 1. Validate phone ─────────────────────────────────────────────────────────

$rawPhone = isset($body['phone']) ? $body['phone'] : '';
$phone    = preg_replace('/\D/', '', $rawPhone);

if (strlen($phone) < 10) {
    json_error('Please enter a valid phone number (at least 10 digits)');
}

// ── 2. Find or create loyalty customer ───────────────────────────────────────

$customer = find_or_create_loyalty_customer($db, $phone, '', $nowMs);

// Fetch full customer record for the response
$stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$customer['id']]);
$fullCustomer = $stmt->fetch();

// ── 3. Load Clover config ────────────────────────────────────────────────────

$cfgRows      = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
$cfg          = $cfgRows ?: [];
$ptsPerDollar = max(1, (int)(isset($cfg['points_per_dollar']) ? $cfg['points_per_dollar'] : 1));

// Window: last 20 minutes
$since = $nowMs - (20 * 60 * 1000);

// ── 4a. Look for unprocessed payment in clover_payment_logs ─────────────────

$logStmt = $db->prepare(
    "SELECT payment_id, merchant_id, amount_cents
     FROM clover_payment_logs
     WHERE status = 'no_customer'
       AND customer_id IS NULL
       AND amount_cents > 0
       AND created_at > ?
     ORDER BY created_at DESC
     LIMIT 1"
);
$logStmt->execute([$since]);
$logRow = $logStmt->fetch();

$amountCents = 0;
$paymentId   = null;
$merchantId  = null;
$fromLog     = false;

if ($logRow) {
    $paymentId   = $logRow['payment_id'];
    $merchantId  = $logRow['merchant_id'];
    $amountCents = (int)$logRow['amount_cents'];
    $fromLog     = true;
}

// ── 4b. Fallback: query Clover API directly ───────────────────────────────────

if (!$fromLog) {
    $token = isset($cfg['access_token']) ? $cfg['access_token'] : '';
    $mId   = isset($cfg['merchant_id'])  ? $cfg['merchant_id']  : '';
    $env   = isset($cfg['environment'])  ? $cfg['environment']  : 'sandbox';

    if ($token && $mId) {
        $base = ($env === 'production')
            ? 'https://api.clover.com'
            : 'https://apisandbox.dev.clover.com';

        $apiUrl = $base . "/v3/merchants/{$mId}/payments?filter=createdTime%3E{$since}&limit=10";

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
        ]);
        $apiBody = curl_exec($ch);
        $apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($apiCode === 200 && $apiBody) {
            $apiResult  = json_decode($apiBody, true);
            $apiPayments = isset($apiResult['elements']) ? $apiResult['elements'] : [];

            // Find first payment NOT already processed
            foreach ($apiPayments as $p) {
                $pid = isset($p['id']) ? $p['id'] : '';
                if (!$pid) continue;
                if ((int)(isset($p['amount']) ? $p['amount'] : 0) <= 0) continue;

                $chk = $db->prepare(
                    "SELECT id FROM clover_payment_logs WHERE payment_id = ? AND status = 'processed'"
                );
                $chk->execute([$pid]);
                if ($chk->fetch()) continue;

                // Found a payment not yet processed
                $paymentId   = $pid;
                $merchantId  = $mId;
                $amountCents = (int)$p['amount'];
                $fromLog     = false;
                break;
            }
        }
    }
}

// ── 5. No payment found ───────────────────────────────────────────────────────

if (!$paymentId || $amountCents <= 0) {
    json_success([
        'status'       => 'no_payment',
        'message'      => 'No recent payment found. Please see staff.',
        'customer'     => $fullCustomer,
        'points_earned' => 0,
    ]);
}

// ── 6. Award points (in transaction) ─────────────────────────────────────────

$pts = intdiv($amountCents, 100) * $ptsPerDollar;

$db->beginTransaction();
try {
    // Lock customer row
    $lockStmt = $db->prepare('SELECT points, tier FROM customers WHERE id = ? FOR UPDATE');
    $lockStmt->execute([$customer['id']]);
    $locked = $lockStmt->fetch();

    $newPts = $locked['points'] + $pts;
    $tier   = tier_from_points($newPts);

    $db->prepare('UPDATE customers SET points = ?, tier = ?, updated_at = ? WHERE id = ?')
       ->execute([$newPts, $tier, $nowMs, $customer['id']]);

    $db->prepare(
        'INSERT INTO loyalty_transactions (id, customer_id, type, points, description, created_at)
         VALUES (?, ?, \'EARNED\', ?, ?, ?)'
    )->execute([uuid4(), $customer['id'], $pts, "Kiosk claim {$paymentId}", $nowMs]);

    if ($fromLog) {
        // Mark the existing log row as processed (race-condition safe)
        $upd = $db->prepare(
            "UPDATE clover_payment_logs
             SET customer_id = ?, phone = ?, points_awarded = ?, status = 'processed', note = 'Kiosk claim'
             WHERE payment_id = ? AND status = 'no_customer'"
        );
        $upd->execute([$customer['id'], $phone, $pts, $paymentId]);

        if ($upd->rowCount() === 0) {
            // Another process already claimed it — roll back and report no payment
            $db->rollBack();
            json_success([
                'status'       => 'no_payment',
                'message'      => 'No recent payment found. Please see staff.',
                'customer'     => $fullCustomer,
                'points_earned' => 0,
            ]);
        }
    } else {
        // Insert from Clover API result — IGNORE handles duplicate payment_id race
        $db->prepare(
            'INSERT IGNORE INTO clover_payment_logs
             (payment_id, merchant_id, customer_id, phone, amount_cents, points_awarded, status, note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, \'processed\', \'Kiosk claim\', ?)'
        )->execute([$paymentId, $merchantId, $customer['id'], $phone, $amountCents, $pts, $nowMs]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    json_error('Could not award points. Please try again or see staff.', 500);
}

// Refresh customer record
$stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$customer['id']]);
$fullCustomer = $stmt->fetch();

json_success([
    'status'       => 'ok',
    'customer'     => $fullCustomer,
    'points_earned' => $pts,
    'new_total'    => $newPts,
    'tier'         => $tier,
    'amount'       => number_format($amountCents / 100, 2),
]);
