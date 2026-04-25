<?php
// Auto-sync Clover payments — called by cron job every hour
// Secured by a secret key to prevent unauthorized access
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Simple secret key check — change this value, keep it private
define('CRON_SECRET', 'stonepho_cron_2024');

$key = $_GET['key'] ?? $_SERVER['HTTP_X_CRON_KEY'] ?? '';
if ($key !== CRON_SECRET) {
    http_response_code(403);
    die('Forbidden');
}

$db  = get_db();
$cfg = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);

$token        = $cfg['access_token']    ?? '';
$mId          = $cfg['merchant_id']     ?? '';
$env          = $cfg['environment']     ?? 'sandbox';
$ptsPerDollar = max(1, (int)($cfg['points_per_dollar'] ?? 1));
$nowMs        = (int)(microtime(true) * 1000);
$base         = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';

if (!$token || !$mId) {
    die(json_encode(['error' => 'No Clover config']));
}

$lastSync = (int)($cfg['last_sync_at'] ?? 0);
if (!$lastSync) $lastSync = $nowMs - (2 * 60 * 60 * 1000); // default: last 2 hours

// Fetch payments since last sync
$url = $base . "/v3/merchants/{$mId}/payments?filter=createdTime%3E{$lastSync}&expand=order&limit=200";
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$token, 'Accept: application/json'],
]);
$body     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die(json_encode(['error' => "Clover API HTTP $httpCode"]));
}

$payments  = json_decode($body, true)['elements'] ?? [];
$processed = $skipped = $noPhone = 0;

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
        $ch2 = curl_init($base . "/v3/merchants/{$mId}/orders/{$orderId}?expand=customers,customers.phoneNumbers");
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
        $ob  = curl_exec($ch2); curl_close($ch2);
        $ord = json_decode($ob, true);
        foreach ($ord['customers']['elements'] ?? [] as $cc) {
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
        $db->prepare('INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,amount_cents,points_awarded,status,note,created_at) VALUES (?,?,?,?,0,"no_customer","No phone on order",?)')->execute([$paymentId,$mId,$orderId,$amountCents,$nowMs]);
        $noPhone++;
        continue;
    }

    $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone=? OR phone=? LIMIT 1');
    $stmt->execute([$phone, ltrim($phone,'1')]);
    $customer = $stmt->fetch();

    if (!$customer) {
        // Auto-create new loyalty member for first-time customers
        $cloverName = '';
        $customer = find_or_create_loyalty_customer($db, $phone, $cloverName, $nowMs);
    }

    $pts = intdiv($amountCents, 100) * $ptsPerDollar;
    if ($pts > 0) {
        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT points FROM customers WHERE id=? FOR UPDATE');
            $s->execute([$customer['id']]);
            $cur    = $s->fetch();
            $newPts = $cur['points'] + $pts;
            $tier   = tier_from_points($newPts);
            $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')->execute([$newPts,$tier,$nowMs,$customer['id']]);
            $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')->execute([uuid4(),$customer['id'],$pts,"Auto sync $paymentId",$nowMs]);
            $db->commit();
        } catch (Exception $e) { $db->rollBack(); $pts = 0; }
    }

    $db->prepare('INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,customer_id,phone,amount_cents,points_awarded,status,created_at) VALUES (?,?,?,?,?,?,?,"processed",?)')->execute([$paymentId,$mId,$orderId,$customer['id'],$phone,$amountCents,$pts,$nowMs]);
    $processed++;
}

// Update last sync timestamp
$db->prepare("INSERT INTO clover_config (config_key,config_val,updated_at) VALUES ('last_sync_at',?,?) ON DUPLICATE KEY UPDATE config_val=VALUES(config_val),updated_at=VALUES(updated_at)")->execute([$nowMs,$nowMs]);

// Log the cron run
$db->prepare("INSERT INTO clover_config (config_key,config_val,updated_at) VALUES ('last_cron_at',?,?) ON DUPLICATE KEY UPDATE config_val=VALUES(config_val),updated_at=VALUES(updated_at)")->execute([$nowMs,$nowMs]);

header('Content-Type: application/json');
echo json_encode([
    'ok'        => true,
    'time'      => date('Y-m-d H:i:s'),
    'processed' => $processed,
    'skipped'   => $skipped,
    'no_phone'  => $noPhone,
    'payments'  => count($payments),
]);
