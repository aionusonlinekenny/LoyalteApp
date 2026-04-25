<?php
// Kiosk self-service endpoints (no auth required)
// POST /api/kiosk/claim   — enter phone, earn points for recent payment
// GET  /api/kiosk/rewards — list active rewards
// POST /api/kiosk/redeem  — redeem a reward by phone + reward_id

$db    = get_db();
$nowMs = (int)(microtime(true) * 1000);

// ── GET /api/kiosk/rewards ────────────────────────────────────────────────────
if ($method === 'GET' && $id === 'rewards') {
    $stmt = $db->query("SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required ASC");
    json_success(['rewards' => $stmt->fetchAll()]);
}

// ── POST /api/kiosk/redeem ────────────────────────────────────────────────────
if ($method === 'POST' && $id === 'redeem') {
    $body     = json_body();
    $phone    = preg_replace('/\D/', '', isset($body['phone'])     ? $body['phone']     : '');
    $rewardId = trim(                  isset($body['reward_id'])   ? $body['reward_id'] : '');

    if (strlen($phone) < 10) json_error('Invalid phone number');
    if (!$rewardId)           json_error('reward_id required');

    $stmt = $db->prepare('SELECT * FROM customers WHERE phone=? OR phone=? LIMIT 1');
    $stmt->execute([$phone, ltrim($phone, '1')]);
    $customer = $stmt->fetch();
    if (!$customer) json_error('Customer not found');

    $stmt = $db->prepare("SELECT * FROM rewards WHERE id=? AND is_active=1");
    $stmt->execute([$rewardId]);
    $reward = $stmt->fetch();
    if (!$reward) json_error('Reward not available');

    if ((int)$customer['points'] < (int)$reward['points_required']) {
        json_error('Not enough points. You have ' . $customer['points'] . ' pts, need ' . $reward['points_required'] . ' pts.');
    }

    $db->beginTransaction();
    try {
        $s = $db->prepare('SELECT points FROM customers WHERE id=? FOR UPDATE');
        $s->execute([$customer['id']]);
        $cur = $s->fetch();

        if ((int)$cur['points'] < (int)$reward['points_required']) {
            $db->rollBack();
            json_error('Not enough points');
        }

        $newPts = (int)$cur['points'] - (int)$reward['points_required'];
        $tier   = tier_from_points($newPts);

        $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')
           ->execute([$newPts, $tier, $nowMs, $customer['id']]);
        $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,\'REDEEMED\',?,?,?)')
           ->execute([uuid4(), $customer['id'], (int)$reward['points_required'], 'Kiosk redeem: ' . $reward['name'], $nowMs]);
        $db->prepare('INSERT INTO redemptions (id,customer_id,reward_id,points_used,redeemed_at) VALUES (?,?,?,?,?)')
           ->execute([uuid4(), $customer['id'], $reward['id'], (int)$reward['points_required'], $nowMs]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Redemption failed. Please try again.');
    }

    json_success([
        'reward_name' => $reward['name'],
        'points_used' => (int)$reward['points_required'],
        'new_points'  => $newPts,
        'tier'        => $tier,
    ]);
}

// ── POST /api/kiosk/claim ─────────────────────────────────────────────────────
if ($method !== 'POST' || $id !== 'claim') {
    json_error('Not found', 404);
}

$body  = json_body();
$phone = preg_replace('/\D/', '', isset($body['phone']) ? $body['phone'] : '');

if (strlen($phone) < 10) {
    json_error('Please enter a valid phone number (at least 10 digits)');
}

// Find or create customer
$customer = find_or_create_loyalty_customer($db, $phone, '', $nowMs);

$stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$customer['id']]);
$fullCustomer = $stmt->fetch();

// Load Clover config
$cfgRows      = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
$cfg          = $cfgRows ?: [];
$ptsPerDollar = max(1, (int)(isset($cfg['points_per_dollar']) ? $cfg['points_per_dollar'] : 1));
$since        = $nowMs - (20 * 60 * 1000);

// Step A: unprocessed payment in clover_payment_logs
$logStmt = $db->prepare(
    "SELECT payment_id, merchant_id, amount_cents
     FROM clover_payment_logs
     WHERE status = 'no_customer' AND customer_id IS NULL AND amount_cents > 0 AND created_at > ?
     ORDER BY created_at DESC LIMIT 1"
);
$logStmt->execute([$since]);
$logRow = $logStmt->fetch();

$amountCents = 0; $paymentId = null; $merchantId = null; $fromLog = false;

if ($logRow) {
    $paymentId = $logRow['payment_id']; $merchantId = $logRow['merchant_id'];
    $amountCents = (int)$logRow['amount_cents']; $fromLog = true;
}

// Step B: fallback — Clover API
if (!$fromLog) {
    $token = isset($cfg['access_token']) ? $cfg['access_token'] : '';
    $mId   = isset($cfg['merchant_id'])  ? $cfg['merchant_id']  : '';
    $env   = isset($cfg['environment'])  ? $cfg['environment']  : 'sandbox';
    if ($token && $mId) {
        $base = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
        $ch   = curl_init($base . "/v3/merchants/{$mId}/payments?filter=createdTime%3E{$since}&limit=10");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
        $apiBody = curl_exec($ch); $apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($apiCode === 200 && $apiBody) {
            foreach (json_decode($apiBody, true)['elements'] ?? [] as $p) {
                $pid = isset($p['id']) ? $p['id'] : '';
                if (!$pid || (int)(isset($p['amount']) ? $p['amount'] : 0) <= 0) continue;
                $chk = $db->prepare("SELECT id FROM clover_payment_logs WHERE payment_id=? AND status='processed'");
                $chk->execute([$pid]);
                if ($chk->fetch()) continue;
                $paymentId = $pid; $merchantId = $mId; $amountCents = (int)$p['amount']; break;
            }
        }
    }
}

// Fetch active rewards to include in response
$rewardsStmt = $db->query("SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required ASC");
$activeRewards = $rewardsStmt->fetchAll();

if (!$paymentId || $amountCents <= 0) {
    json_success([
        'status'        => 'no_payment',
        'message'       => 'No recent payment found. Please see staff.',
        'customer'      => $fullCustomer,
        'points_earned' => 0,
        'new_total'     => (int)$fullCustomer['points'],
        'tier'          => $fullCustomer['tier'],
        'rewards'       => $activeRewards,
    ]);
}

$pts = intdiv($amountCents, 100) * $ptsPerDollar;

$db->beginTransaction();
try {
    $lockStmt = $db->prepare('SELECT points, tier FROM customers WHERE id = ? FOR UPDATE');
    $lockStmt->execute([$customer['id']]);
    $locked = $lockStmt->fetch();
    $newPts = $locked['points'] + $pts;
    $tier   = tier_from_points($newPts);

    $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')
       ->execute([$newPts, $tier, $nowMs, $customer['id']]);
    $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,\'EARNED\',?,?,?)')
       ->execute([uuid4(), $customer['id'], $pts, "Kiosk claim {$paymentId}", $nowMs]);

    if ($fromLog) {
        $upd = $db->prepare("UPDATE clover_payment_logs SET customer_id=?,phone=?,points_awarded=?,status='processed',note='Kiosk claim' WHERE payment_id=? AND status='no_customer'");
        $upd->execute([$customer['id'], $phone, $pts, $paymentId]);
        if ($upd->rowCount() === 0) {
            $db->rollBack();
            json_success(['status'=>'no_payment','message'=>'No recent payment found.','customer'=>$fullCustomer,'points_earned'=>0,'new_total'=>(int)$fullCustomer['points'],'tier'=>$fullCustomer['tier'],'rewards'=>$activeRewards]);
        }
    } else {
        $db->prepare("INSERT IGNORE INTO clover_payment_logs (payment_id,merchant_id,customer_id,phone,amount_cents,points_awarded,status,note,created_at) VALUES (?,?,?,?,?,?,'processed','Kiosk claim',?)")
           ->execute([$paymentId, $merchantId, $customer['id'], $phone, $amountCents, $pts, $nowMs]);
    }
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    json_error('Could not award points. Please try again.', 500);
}

$stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$customer['id']]);
$fullCustomer = $stmt->fetch();

json_success([
    'status'        => 'ok',
    'customer'      => $fullCustomer,
    'points_earned' => $pts,
    'new_total'     => $newPts,
    'tier'          => $tier,
    'amount'        => number_format($amountCents / 100, 2),
    'rewards'       => $activeRewards,
]);
