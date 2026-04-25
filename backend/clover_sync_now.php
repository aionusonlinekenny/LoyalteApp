<?php
// One-click Clover sync — open in browser to sync Clover payments
// DELETE this file from server after setup is complete!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: text/html; charset=utf-8');

$db  = get_db();
$cfg = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle: delete a specific failed log entry so it gets re-synced
if (isset($_GET['retry_payment'])) {
    $pid = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['retry_payment']));
    if ($pid) {
        $db->prepare("DELETE FROM clover_payment_logs WHERE payment_id = ? AND status IN ('no_customer','error')")->execute([$pid]);
        echo "<p style='color:green;font-family:sans-serif'>Deleted failed log for $pid — reload to re-sync.</p>";
    }
    exit;
}

// Handle: reset last_sync_at to N hours ago so we re-fetch older payments
if (isset($_GET['reset_hours'])) {
    $h = max(1, min(72, (int)$_GET['reset_hours']));
    $ts = (int)(microtime(true) * 1000) - ($h * 3600 * 1000);
    $db->prepare("INSERT INTO clover_config (config_key,config_val,updated_at) VALUES ('last_sync_at',?,?) ON DUPLICATE KEY UPDATE config_val=VALUES(config_val),updated_at=VALUES(updated_at)")->execute([$ts, $ts]);
    echo "<p style='color:green;font-family:sans-serif'>Reset last_sync_at to $h hours ago — <a href='clover_sync_now.php'>run sync now</a>.</p>";
    exit;
}

$token        = $cfg['access_token']    ?? '';
$mId          = $cfg['merchant_id']     ?? '';
$env          = $cfg['environment']     ?? 'sandbox';
$ptsPerDollar = max(1, (int)($cfg['points_per_dollar'] ?? 1));
$nowMs        = (int)(microtime(true) * 1000);

if (!$token || !$mId) {
    die('<h2 style="color:red">❌ No access token or merchant ID saved yet.<br>Go to Admin → Loyalty → Clover POS and save settings first.</h2>');
}

// Call Clover API
$base     = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
$lastSync = (int)($cfg['last_sync_at'] ?? 0);
if (!$lastSync) $lastSync = $nowMs - (24 * 60 * 60 * 1000);

$url = $base . "/v3/merchants/{$mId}/payments?filter=createdTime%3E{$lastSync}&expand=order&limit=100";
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
]);
$body     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("<h2 style='color:red'>❌ Clover API error (HTTP $httpCode)<br>Check your Access Token and Merchant ID.</h2><pre>$body</pre>");
}

$result   = json_decode($body, true);
$payments = $result['elements'] ?? [];

$processed = $skipped = $noPhone = 0;
$rows = '';

foreach ($payments as $payment) {
    $paymentId   = $payment['id'] ?? '';
    $amountCents = (int)($payment['amount'] ?? 0);
    $orderId     = $payment['order']['id'] ?? null;
    if (!$paymentId) continue;

    $check = $db->prepare('SELECT id FROM clover_payment_logs WHERE payment_id = ?');
    $check->execute([$paymentId]);
    if ($check->fetch()) {
        $skipped++;
        $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:gray'>Already processed</td><td>—</td></tr>";
        continue;
    }

    $phone = null;
    if ($orderId) {
        $ch2  = curl_init($base . "/v3/merchants/{$mId}/orders/{$orderId}?expand=customers,customers.phoneNumbers");
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Accept: application/json']]);
        $ob   = curl_exec($ch2); curl_close($ch2);
        $ord  = json_decode($ob, true);
        foreach ($ord['customers']['elements'] ?? [] as $cc) {
            // Try direct phoneNumber first, then nested phoneNumbers.elements
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
        // Debug: show raw order JSON so we can see what Clover actually returned
        $debugDump = isset($ord) ? htmlspecialchars(json_encode($ord, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) : 'no order fetch attempted';
        $db->prepare('INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,amount_cents,points_awarded,status,note,created_at) VALUES (?,?,?,?,0,"no_customer","No phone on order",?)')->execute([$paymentId,$mId,$orderId,$amountCents,$nowMs]);
        $noPhone++;
        $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:orange'>No phone — <details><summary>Raw order JSON</summary><pre style='font-size:11px;max-height:300px;overflow:auto'>$debugDump</pre></details></td><td>0</td></tr>";
        continue;
    }

    $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
    $stmt->execute([$phone, ltrim($phone,'1')]);
    $customer = $stmt->fetch();

    if (!$customer) {
        $db->prepare('INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,phone,amount_cents,points_awarded,status,note,created_at) VALUES (?,?,?,?,?,0,"no_customer","Not in loyalty program",?)')->execute([$paymentId,$mId,$orderId,$phone,$amountCents,$nowMs]);
        $noPhone++;
        $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:orange'>$phone — not in program</td><td>0</td></tr>";
        continue;
    }

    $pts = intdiv($amountCents, 100) * $ptsPerDollar;
    if ($pts > 0) {
        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
            $s->execute([$customer['id']]);
            $cur = $s->fetch();
            $newPts = $cur['points'] + $pts;
            $tier   = tier_from_points($newPts);
            $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')->execute([$newPts,$tier,$nowMs,$customer['id']]);
            $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')->execute([uuid4(),$customer['id'],$pts,"Clover sync $paymentId",$nowMs]);
            $db->commit();
        } catch (Exception $e) { $db->rollBack(); $pts = 0; }
    }
    $db->prepare('INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,customer_id,phone,amount_cents,points_awarded,status,created_at) VALUES (?,?,?,?,?,?,?,"processed",?)')->execute([$paymentId,$mId,$orderId,$customer['id'],$phone,$amountCents,$pts,$nowMs]);
    $processed++;
    $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:green'>✅ +{$pts} pts to $phone</td><td>$pts</td></tr>";
}

$db->prepare("INSERT INTO clover_config (config_key,config_val,updated_at) VALUES ('last_sync_at',?,?) ON DUPLICATE KEY UPDATE config_val=VALUES(config_val),updated_at=VALUES(updated_at)")->execute([$nowMs,$nowMs]);
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Sync Result</title>
<style>body{font-family:sans-serif;padding:30px;max-width:900px}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{padding:8px 12px;border:1px solid #ddd;text-align:left}th{background:#f5f5f5}.box{padding:16px;border-radius:8px;margin-bottom:16px}.ok{background:#e8f5e9;border:1px solid #a5d6a7}.warn{background:#fff3e0;border:1px solid #ffb74d}</style>
</head><body>
<h2>Clover Payment Sync — <?= date('Y-m-d H:i:s') ?></h2>
<div class="box ok">
  <strong>Total payments fetched:</strong> <?= count($payments) ?><br>
  <strong style="color:green">✅ Points awarded:</strong> <?= $processed ?> payments<br>
  <strong style="color:gray">⏭ Already processed:</strong> <?= $skipped ?><br>
  <strong style="color:orange">📵 No phone match:</strong> <?= $noPhone ?>
</div>
<?php if ($noPhone > 0): ?>
<div class="box warn">💡 "No phone match" means the customer paid but their phone number is not in the loyalty system, or not attached to their Clover order.</div>
<?php endif; ?>
<table><thead><tr><th>Payment ID</th><th>Amount</th><th>Result</th><th>Points</th></tr></thead>
<tbody><?= $rows ?: '<tr><td colspan="4" style="text-align:center;color:gray">No payments in last 24 hours</td></tr>' ?></tbody>
</table>
<div style="margin-top:30px;padding:14px;background:#fff3e0;border:1px solid #ffb74d;border-radius:8px;font-family:sans-serif;font-size:14px">
  <b>Re-sync a failed payment:</b>
  <a href="?retry_payment=9M9M1573VZFER" style="margin-left:8px;background:#1565c0;color:#fff;padding:4px 12px;border-radius:4px;text-decoration:none">Retry 9M9M1573VZFER</a>
  &nbsp;or reset sync window:
  <a href="?reset_hours=6" style="background:#4caf50;color:#fff;padding:4px 10px;border-radius:4px;text-decoration:none">Last 6h</a>
  <a href="?reset_hours=24" style="margin-left:4px;background:#4caf50;color:#fff;padding:4px 10px;border-radius:4px;text-decoration:none">Last 24h</a>
  <a href="?reset_hours=48" style="margin-left:4px;background:#4caf50;color:#fff;padding:4px 10px;border-radius:4px;text-decoration:none">Last 48h</a>
</div>
<p style="color:#999;margin-top:30px">⚠️ Delete this file (clover_sync_now.php) from your server after testing.</p>
</body></html>
