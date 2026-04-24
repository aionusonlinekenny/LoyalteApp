<?php
// Backfill loyalty points from historical Clover payments
// Runs in batches of 50 — keep clicking Next until done
// DELETE this file after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: text/html; charset=utf-8');

$db  = get_db();
$cfg = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);

$token        = $cfg['access_token']    ?? '';
$mId          = $cfg['merchant_id']     ?? '';
$env          = $cfg['environment']     ?? 'sandbox';
$ptsPerDollar = max(1, (int)($cfg['points_per_dollar'] ?? 1));
$nowMs        = (int)(microtime(true) * 1000);

if (!$token || !$mId) {
    die('<h2 style="color:red">❌ No access token or merchant ID saved.</h2>');
}

$base   = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
$offset = max(0, (int)($_GET['offset'] ?? 0));
$days   = max(1, (int)($_GET['days']   ?? 90));
$limit  = 50; // small batch to avoid timeout

$fromMs = $nowMs - ($days * 24 * 60 * 60 * 1000);

// Fetch one batch of payments
$url = $base . "/v3/merchants/{$mId}/payments"
     . "?filter=createdTime%3E{$fromMs}"
     . "&expand=order"
     . "&limit={$limit}&offset={$offset}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
]);
$body     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("<h2 style='color:red'>❌ Clover API error (HTTP $httpCode)</h2><pre>" . htmlspecialchars($body) . "</pre>");
}

$result   = json_decode($body, true);
$payments = $result['elements'] ?? [];
$hasMore  = count($payments) === $limit;

$processed = $skipped = $noPhone = $notInProgram = 0;
$rows = '';

foreach ($payments as $payment) {
    $paymentId   = $payment['id']             ?? '';
    $amountCents = (int)($payment['amount']   ?? 0);
    $orderId     = $payment['order']['id']    ?? null;
    if (!$paymentId || $amountCents <= 0) continue;

    // Already processed?
    $check = $db->prepare('SELECT id FROM clover_payment_logs WHERE payment_id = ?');
    $check->execute([$paymentId]);
    if ($check->fetch()) {
        $skipped++;
        continue;
    }

    // Get customer phone from order
    $phone = null;
    if ($orderId) {
        $ch2 = curl_init($base . "/v3/merchants/{$mId}/orders/{$orderId}?expand=customers");
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
        ]);
        $ob  = curl_exec($ch2); curl_close($ch2);
        $ord = json_decode($ob, true);
        foreach ($ord['customers']['elements'] ?? [] as $cc) {
            $raw = preg_replace('/\D/', '', $cc['phoneNumber'] ?? '');
            if ($raw) { $phone = $raw; break; }
        }
    }

    if (!$phone) {
        $db->prepare(
            'INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,amount_cents,points_awarded,status,note,created_at)
             VALUES (?,?,?,?,0,"no_customer","No phone on order",?)'
        )->execute([$paymentId, $mId, $orderId, $amountCents, $nowMs]);
        $noPhone++;
        $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:#aaa'>No phone on order</td><td>0</td></tr>";
        continue;
    }

    // Match loyalty customer
    $stmt = $db->prepare('SELECT id, name, points FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
    $stmt->execute([$phone, ltrim($phone, '1')]);
    $customer = $stmt->fetch();

    if (!$customer) {
        $db->prepare(
            'INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,phone,amount_cents,points_awarded,status,note,created_at)
             VALUES (?,?,?,?,?,0,"no_customer","Not in loyalty program",?)'
        )->execute([$paymentId, $mId, $orderId, $phone, $amountCents, $nowMs]);
        $notInProgram++;
        $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:orange'>$phone — not enrolled</td><td>0</td></tr>";
        continue;
    }

    // Award points
    $pts = intdiv($amountCents, 100) * $ptsPerDollar;
    if ($pts > 0) {
        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
            $s->execute([$customer['id']]);
            $cur       = $s->fetch();
            $newPoints = $cur['points'] + $pts;
            $tier      = tier_from_points($newPoints);
            $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')
               ->execute([$newPoints, $tier, $nowMs, $customer['id']]);
            $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
               ->execute([uuid4(), $customer['id'], $pts, "Clover backfill $paymentId", $nowMs]);
            $db->commit();
        } catch (Exception $e) { $db->rollBack(); $pts = 0; }
    }

    $db->prepare(
        'INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,customer_id,phone,amount_cents,points_awarded,status,created_at)
         VALUES (?,?,?,?,?,?,?,"processed",?)'
    )->execute([$paymentId, $mId, $orderId, $customer['id'], $phone, $amountCents, $pts, $nowMs]);

    $processed++;
    $name = htmlspecialchars($customer['name']);
    $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:green'>✅ $name ($phone)</td><td>+$pts</td></tr>";
}

$nextOffset = $offset + $limit;
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Points Backfill</title>
<style>
  body{font-family:sans-serif;padding:24px;max-width:960px}
  table{width:100%;border-collapse:collapse;margin-top:16px}
  th,td{padding:8px 12px;border:1px solid #ddd;font-size:13px;text-align:left}
  th{background:#f5f5f5}
  .box{padding:14px 18px;border-radius:8px;margin-bottom:14px}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7}
  .info{background:#e3f2fd;border:1px solid #90caf9}
  .btn{display:inline-block;padding:12px 28px;background:#1565c0;color:white;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;margin-top:16px}
  .done{background:#2e7d32;color:white;padding:16px 20px;border-radius:8px;margin-top:16px;font-size:15px}
</style>
</head><body>

<h2>Clover Points Backfill — Last <?= $days ?> days</h2>

<?php if ($offset === 0): ?>
<div class="box info">
  📅 Fetching payments from <strong><?= date('M d, Y', $fromMs/1000) ?></strong> to now.
  Each batch processes <?= $limit ?> payments. Keep clicking <strong>Next Batch</strong> until done.
  <br><br>
  Change period:
  <a href="?days=30">30 days</a> |
  <a href="?days=60">60 days</a> |
  <a href="?days=90">90 days</a> |
  <a href="?days=180">6 months</a> |
  <a href="?days=365">1 year</a>
</div>
<?php endif; ?>

<div class="box ok">
  <strong>Batch <?= floor($offset/$limit)+1 ?> (payments <?= $offset+1 ?>–<?= $offset+count($payments) ?>):</strong><br>
  ✅ Points awarded: <strong><?= $processed ?></strong> &nbsp;|&nbsp;
  ⏭ Already done: <strong><?= $skipped ?></strong> &nbsp;|&nbsp;
  📵 No phone: <strong><?= $noPhone ?></strong> &nbsp;|&nbsp;
  ❌ Not enrolled: <strong><?= $notInProgram ?></strong>
</div>

<?php if (!empty($rows)): ?>
<table>
  <thead><tr><th>Payment ID</th><th>Amount</th><th>Customer</th><th>Points</th></tr></thead>
  <tbody><?= $rows ?></tbody>
</table>
<?php endif; ?>

<?php if ($hasMore): ?>
  <a class="btn" href="?offset=<?= $nextOffset ?>&days=<?= $days ?>">
    ▶ Next batch (<?= $nextOffset ?>+)
  </a>
<?php else: ?>
  <div class="done">
    🎉 All done! All <?= $days ?>-day payments have been processed.<br>
    <small>Delete clover_backfill_points.php from your server.</small>
  </div>
<?php endif; ?>

</body></html>
