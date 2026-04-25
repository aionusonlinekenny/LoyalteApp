<?php
// POST /api/clover/webhook          Clover webhook — no auth (verified by appId)
// GET  /api/clover/webhook          Clover webhook URL verification
// GET  /api/clover/config           get Clover config (staff)
// PUT  /api/clover/config           save Clover config (staff)
// GET  /api/clover/logs             recent payment logs (staff)

// ── helpers ───────────────────────────────────────────────────────────────────

function clover_get_config(PDO $db): array {
    $rows = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $rows ?: [];
}

function clover_set_config(PDO $db, string $key, string $val): void {
    $nowMs = (int)(microtime(true) * 1000);
    $db->prepare(
        'INSERT INTO clover_config (config_key, config_val, updated_at)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE config_val = VALUES(config_val), updated_at = VALUES(updated_at)'
    )->execute([$key, $val, $nowMs]);
}

function clover_api(string $cfg_env, string $token, string $path): ?array {
    $base = ($cfg_env === 'production')
        ? 'https://api.clover.com'
        : 'https://apisandbox.dev.clover.com';

    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$body) return null;
    return json_decode($body, true);
}

// ── GET /api/clover/webhook — Clover verification challenge ──────────────────
if ($method === 'GET' && $id === 'webhook') {
    $code = $_GET['verificationCode'] ?? '';
    // Log everything Clover sends so we can debug
    $log = date('Y-m-d H:i:s') . "\n"
         . 'GET params: ' . json_encode($_GET) . "\n"
         . 'Headers: ' . json_encode(getallheaders()) . "\n";
    file_put_contents(__DIR__ . '/../clover_verify.txt', $log);
    header('Content-Type: text/plain');
    echo $code;
    exit;
}

// ── POST /api/clover/webhook ─────────────────────────────────────────────────
if ($method === 'POST' && $id === 'webhook') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];

    // Clover sends POST with {"verificationCode":"..."} to verify the URL
    if (isset($data['verificationCode'])) {
        http_response_code(200);
        echo json_encode(['verificationCode' => $data['verificationCode']]);
        exit;
    }

    $db     = get_db();
    $cfg    = clover_get_config($db);
    $nowMs  = (int)(microtime(true) * 1000);

    if (empty($cfg['enabled']) || $cfg['enabled'] !== '1') {
        http_response_code(200);
        echo json_encode(['ok' => true, 'skipped' => 'disabled']);
        exit;
    }

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data) {
        http_response_code(200); // always 200 to Clover
        exit;
    }

    // Clover sends: { "appId": "...", "merchants": { "mId": { "payments": { "paymentId": timestamp } } } }
    $incomingApp = $data['appId'] ?? '';
    if ($cfg['app_id'] && $incomingApp !== $cfg['app_id']) {
        http_response_code(200);
        exit;
    }

    $merchants = $data['merchants'] ?? [];
    $token     = $cfg['access_token'] ?? '';
    $env       = $cfg['environment']  ?? 'sandbox';
    $ptsPerDollar = max(1, (int)($cfg['points_per_dollar'] ?? 1));

    foreach ($merchants as $mId => $mData) {
        $payments = $mData['payments'] ?? [];
        foreach ($payments as $paymentId => $_ts) {
            // Skip already processed
            $check = $db->prepare('SELECT id FROM clover_payment_logs WHERE payment_id = ?');
            $check->execute([$paymentId]);
            if ($check->fetch()) continue;

            // Fetch payment details from Clover
            $payment = clover_api($env, $token, "/v3/merchants/{$mId}/payments/{$paymentId}?expand=order");

            if (!$payment) {
                $db->prepare(
                    'INSERT INTO clover_payment_logs (payment_id, merchant_id, status, note, created_at)
                     VALUES (?, ?, \'error\', \'Clover API call failed\', ?)'
                )->execute([$paymentId, $mId, $nowMs]);
                continue;
            }

            $amountCents = (int)($payment['amount'] ?? 0);
            $orderId     = $payment['order']['id'] ?? null;

            // Get customer phone from order
            $phone = null;
            if ($orderId) {
                $order = clover_api($env, $token, "/v3/merchants/{$mId}/orders/{$orderId}?expand=customers,customers.phoneNumbers");
                $customers = $order['customers']['elements'] ?? [];
                foreach ($customers as $cloverCustomer) {
                    $raw = preg_replace('/\D/', '', $cloverCustomer['phoneNumber'] ?? '');
                    if (!$raw) {
                        foreach ($cloverCustomer['phoneNumbers']['elements'] ?? [] as $pn) {
                            $raw = preg_replace('/\D/', '', $pn['phoneNumber'] ?? '');
                            if ($raw) break;
                        }
                    }
                    if ($raw) { $phone = $raw; break; }
                }
            }

            if (!$phone) {
                $db->prepare(
                    'INSERT INTO clover_payment_logs
                     (payment_id, merchant_id, order_id, amount_cents, points_awarded, status, note, created_at)
                     VALUES (?, ?, ?, ?, 0, \'no_customer\', \'No phone on Clover order\', ?)'
                )->execute([$paymentId, $mId, $orderId, $amountCents, $nowMs]);
                continue;
            }

            // Lookup loyalteapp customer by phone (try with/without country code)
            $stmt = $db->prepare(
                'SELECT id, points, tier FROM customers WHERE phone = ? OR phone = ? LIMIT 1'
            );
            $stmt->execute([$phone, ltrim($phone, '1')]); // strip leading 1 (US)
            $customer = $stmt->fetch();

            if (!$customer) {
                // Auto-create new loyalty member for first-time customers
                $cloverName = '';
                foreach ($customers as $cloverCustomer) {
                    $fn = trim(($cloverCustomer['firstName'] ?? '') . ' ' . ($cloverCustomer['lastName'] ?? ''));
                    if ($fn) { $cloverName = $fn; break; }
                }
                $customer = find_or_create_loyalty_customer($db, $phone, $cloverName, $nowMs);
            }

            // Award points: 1 point per dollar (integer division)
            $dollars = intdiv($amountCents, 100);
            $pts     = $dollars * $ptsPerDollar;

            if ($pts > 0) {
                $db->beginTransaction();
                try {
                    $stmt2 = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
                    $stmt2->execute([$customer['id']]);
                    $cur = $stmt2->fetch();

                    $newPoints = $cur['points'] + $pts;
                    $tier      = tier_from_points($newPoints);

                    $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
                       ->execute([$newPoints, $tier, $nowMs, $customer['id']]);

                    $db->prepare(
                        'INSERT INTO loyalty_transactions (id, customer_id, type, points, description, created_at)
                         VALUES (?, ?, \'EARNED\', ?, ?, ?)'
                    )->execute([uuid4(), $customer['id'], $pts, "Clover payment {$paymentId}", $nowMs]);

                    $db->commit();
                } catch (Exception $e) {
                    $db->rollBack();
                    $pts = 0;
                }
            }

            $db->prepare(
                'INSERT INTO clover_payment_logs
                 (payment_id, merchant_id, order_id, customer_id, phone, amount_cents, points_awarded, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, \'processed\', ?)'
            )->execute([$paymentId, $mId, $orderId, $customer['id'], $phone, $amountCents, $pts, $nowMs]);
        }
    }

    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

// ── All other endpoints require staff auth ────────────────────────────────────
auth_required();
$db = get_db();

// ── GET /api/clover/config ────────────────────────────────────────────────────
if ($method === 'GET' && $id === 'config') {
    $cfg = clover_get_config($db);
    // Never expose app_secret / access_token in full — mask them
    if (!empty($cfg['access_token'])) {
        $cfg['access_token'] = '••••' . substr($cfg['access_token'], -6);
    }
    if (!empty($cfg['app_secret'])) {
        $cfg['app_secret'] = '••••' . substr($cfg['app_secret'], -4);
    }
    json_success(['config' => $cfg]);
}

// ── PUT /api/clover/config ────────────────────────────────────────────────────
if ($method === 'PUT' && $id === 'config') {
    $body    = json_body();
    $allowed = ['app_id', 'app_secret', 'access_token', 'merchant_id', 'environment', 'points_per_dollar', 'enabled'];
    foreach ($allowed as $key) {
        if (array_key_exists($key, $body)) {
            clover_set_config($db, $key, (string)$body[$key]);
        }
    }
    json_success(['message' => 'Config saved']);
}

// ── GET /api/clover/logs ──────────────────────────────────────────────────────
if ($method === 'GET' && $id === 'logs') {
    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $stmt  = $db->prepare(
        'SELECT l.*, c.name AS customer_name
         FROM clover_payment_logs l
         LEFT JOIN customers c ON c.id = l.customer_id
         ORDER BY l.created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$limit]);
    json_success(['logs' => $stmt->fetchAll()]);
}

// ── POST /api/clover/sync — manual pull of recent payments ───────────────────
if ($method === 'POST' && $id === 'sync') {
    $cfg   = clover_get_config($db);
    $token = $cfg['access_token'] ?? '';
    $mId   = $cfg['merchant_id']  ?? '';
    $env   = $cfg['environment']  ?? 'sandbox';
    $ptsPerDollar = max(1, (int)($cfg['points_per_dollar'] ?? 1));
    $nowMs = (int)(microtime(true) * 1000);

    if (!$token || !$mId) json_error('Access token and merchant ID are required');

    // Sync from last 24 hours (or since last sync if stored)
    $lastSync = (int)($cfg['last_sync_at'] ?? 0);
    if (!$lastSync) $lastSync = $nowMs - (24 * 60 * 60 * 1000); // 24h ago

    $result = clover_api($env, $token,
        "/v3/merchants/{$mId}/payments?filter=createdTime%3E{$lastSync}&expand=order&limit=100"
    );

    if ($result === null) json_error('Failed to connect to Clover API. Check your access token and merchant ID.');

    $payments  = $result['elements'] ?? [];
    $processed = 0;
    $skipped   = 0;
    $noPhone   = 0;
    $errors    = 0;

    foreach ($payments as $payment) {
        $paymentId   = $payment['id'] ?? '';
        $amountCents = (int)($payment['amount'] ?? 0);
        $orderId     = $payment['order']['id'] ?? null;

        if (!$paymentId) continue;

        // Skip already processed
        $check = $db->prepare('SELECT id FROM clover_payment_logs WHERE payment_id = ?');
        $check->execute([$paymentId]);
        if ($check->fetch()) { $skipped++; continue; }

        // Get customer phone from order
        $phone = null;
        if ($orderId) {
            $order = clover_api($env, $token, "/v3/merchants/{$mId}/orders/{$orderId}?expand=customers,customers.phoneNumbers");
            $cloverCustomers = $order['customers']['elements'] ?? [];
            foreach ($cloverCustomers as $cc) {
                $raw = preg_replace('/\D/', '', $cc['phoneNumber'] ?? '');
                if (!$raw) {
                    foreach ($cc['phoneNumbers']['elements'] ?? [] as $pn) {
                        $raw = preg_replace('/\D/', '', $pn['phoneNumber'] ?? '');
                        if ($raw) break;
                    }
                }
                if ($raw) { $phone = $raw; break; }
            }
        }

        if (!$phone) {
            $db->prepare(
                'INSERT INTO clover_payment_logs
                 (payment_id, merchant_id, order_id, amount_cents, points_awarded, status, note, created_at)
                 VALUES (?, ?, ?, ?, 0, \'no_customer\', \'No phone on Clover order\', ?)'
            )->execute([$paymentId, $mId, $orderId, $amountCents, $nowMs]);
            $noPhone++;
            continue;
        }

        $stmt = $db->prepare(
            'SELECT id, points FROM customers WHERE phone = ? OR phone = ? LIMIT 1'
        );
        $stmt->execute([$phone, ltrim($phone, '1')]);
        $customer = $stmt->fetch();

        if (!$customer) {
            // Auto-create new loyalty member for first-time customers
            $customer = find_or_create_loyalty_customer($db, $phone, '', $nowMs);
        }

        $pts = intdiv($amountCents, 100) * $ptsPerDollar;

        if ($pts > 0) {
            $db->beginTransaction();
            try {
                $s = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
                $s->execute([$customer['id']]);
                $cur = $s->fetch();

                $newPoints = $cur['points'] + $pts;
                $tier      = tier_from_points($newPoints);

                $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
                   ->execute([$newPoints, $tier, $nowMs, $customer['id']]);
                $db->prepare(
                    'INSERT INTO loyalty_transactions (id, customer_id, type, points, description, created_at)
                     VALUES (?, ?, \'EARNED\', ?, ?, ?)'
                )->execute([uuid4(), $customer['id'], $pts, "Clover sync {$paymentId}", $nowMs]);
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $pts = 0;
                $errors++;
            }
        }

        $db->prepare(
            'INSERT INTO clover_payment_logs
             (payment_id, merchant_id, order_id, customer_id, phone, amount_cents, points_awarded, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'processed\', ?)'
        )->execute([$paymentId, $mId, $orderId, $customer['id'], $phone, $amountCents, $pts, $nowMs]);
        $processed++;
    }

    // Save last sync time
    clover_set_config($db, 'last_sync_at', (string)$nowMs);

    json_success([
        'total'     => count($payments),
        'processed' => $processed,
        'skipped'   => $skipped,
        'no_phone'  => $noPhone,
        'errors'    => $errors,
    ]);
}

json_error('Not found', 404);
