<?php
// Backfill loyalty points from historical Clover payments
// Uses parallel curl requests — 500 per batch. DELETE after use!
set_time_limit(300);
ini_set('max_execution_time', 300);

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
$base         = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';

if (!$token || !$mId) {
    die('<h2 style="color:red">❌ No access token or merchant ID saved.</h2>');
}

$offset = max(0, (int)($_GET['offset'] ?? 0));
$days   = max(1, (int)($_GET['days']   ?? 90));
$limit  = 500;
$fromMs = $nowMs - ($days * 24 * 60 * 60 * 1000);

// ── Parallel curl helper ──────────────────────────────────────────────────────
function fetch_parallel(string $base, string $token, array $paths, int $concurrency = 20): array {
    $results  = [];
    $chunks   = array_chunk($paths, $concurrency, true);

    foreach ($chunks as $chunk) {
        $mh      = curl_multi_init();
        $handles = [];

        foreach ($chunk as $key => $path) {
            $ch = curl_init($base . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json',
                ],
            ]);
            $handles[$key] = $ch;
            curl_multi_add_handle($mh, $ch);
        }

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) curl_multi_select($mh);
        } while ($active && $status == CURLM_OK);

        foreach ($handles as $key => $ch) {
            $body          = curl_multi_getcontent($ch);
            $results[$key] = $body ? json_decode($body, true) : null;
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);
    }

    return $results;
}

// ── Step 1: Fetch 500 payments ────────────────────────────────────────────────
$url = $base . "/v3/merchants/{$mId}/payments"
     . "?filter=createdTime%3E{$fromMs}"
     . "&expand=order&limit={$limit}&offset={$offset}";

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

// ── Step 2: Skip already-processed, collect order IDs needed ─────────────────
$toProcess  = [];
$skipped    = 0;

foreach ($payments as $payment) {
    $paymentId   = $payment['id']          ?? '';
    $amountCents = (int)($payment['amount'] ?? 0);
    $orderId     = $payment['order']['id'] ?? null;

    if (!$paymentId || $amountCents <= 0) continue;

    $check = $db->prepare('SELECT id FROM clover_payment_logs WHERE payment_id = ?');
    $check->execute([$paymentId]);
    if ($check->fetch()) { $skipped++; continue; }

    $toProcess[$paymentId] = ['amount' => $amountCents, 'order_id' => $orderId];
}

// ── Step 3: Fetch all orders in parallel ─────────────────────────────────────
$orderPaths = [];
foreach ($toProcess as $pid => $p) {
    if ($p['order_id']) {
        $orderPaths[$pid] = "/v3/merchants/{$mId}/orders/{$p['order_id']}?expand=customers";
    }
}

$orderResults = fetch_parallel($base, $token, $orderPaths, 20);

// ── Step 4: Process each payment ─────────────────────────────────────────────
$processed = $noPhone = $notInProgram = 0;
$rows = '';

foreach ($toProcess as $paymentId => $p) {
    $amountCents = $p['amount'];
    $orderId     = $p['order_id'];

    // Extract phone from order
    $phone = null;
    if ($orderId && isset($orderResults[$paymentId])) {
        $ord = $orderResults[$paymentId];
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
        $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:#bbb'>No phone</td><td>0</td></tr>";
        continue;
    }

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

$nextOffset    = $offset + $limit;
$totalProcessed = $processed + (int)($_GET['tp'] ?? 0);
$totalSkipped   = $skipped   + (int)($_GET['ts'] ?? 0);
$totalNoPhone   = $noPhone   + (int)($_GET['tn'] ?? 0);
$totalNotIn     = $notInProgram + (int)($_GET['tni'] ?? 0);
$nextUrl = "?offset={$nextOffset}&days={$days}&tp={$totalProcessed}&ts={$totalSkipped}&tn={$totalNoPhone}&tni={$totalNotIn}";
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Points Backfill</title>
<style>
  body{font-family:sans-serif;padding:24px;max-width:1000px}
  table{width:100%;border-collapse:collapse;margin-top:16px}
  th,td{padding:7px 11px;border:1px solid #ddd;font-size:13px;text-align:left}
  th{background:#f5f5f5}
  .box{padding:14px 18px;border-radius:8px;margin-bottom:12px}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7}
  .info{background:#e3f2fd;border:1px solid #90caf9}
  .btn{display:inline-block;padding:12px 32px;background:#1565c0;color:white;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;margin-top:16px}
  .done{background:#2e7d32;color:white;padding:16px 20px;border-radius:8px;margin-top:16px}
  a.day{margin-right:8px;color:#1565c0}
</style>
</head><body>

<h2>Clover Points Backfill — Last <?= $days ?> days</h2>

<?php if ($offset === 0): ?>
<div class="box info">
  📅 From <strong><?= date('M d, Y', $fromMs/1000) ?></strong> to now &nbsp;|&nbsp;
  Change: <a class="day" href="?days=30">30d</a><a class="day" href="?days=60">60d</a><a class="day" href="?days=90">90d</a><a class="day" href="?days=180">6mo</a><a class="day" href="?days=365">1yr</a>
</div>
<?php endif; ?>

<div class="box ok">
  <b>Batch <?= floor($offset/$limit)+1 ?> — payments <?= $offset+1 ?>–<?= $offset+count($payments) ?>:</b><br>
  ✅ Points awarded: <b><?= $processed ?></b> &nbsp;|&nbsp;
  ⏭ Already done: <b><?= $skipped ?></b> &nbsp;|&nbsp;
  📵 No phone: <b><?= $noPhone ?></b> &nbsp;|&nbsp;
  ❌ Not enrolled: <b><?= $notInProgram ?></b>
</div>

<?php if (!empty($rows)): ?>
<table>
  <thead><tr><th>Payment ID</th><th>Amount</th><th>Customer</th><th>Points</th></tr></thead>
  <tbody><?= $rows ?></tbody>
</table>
<?php endif; ?>

<?php if ($hasMore): ?>
<div class="box" style="background:#fff8e1;border:1px solid #ffe082;margin-top:16px">
  <b>Running totals:</b>
  ✅ Awarded: <b><?= $totalProcessed ?></b> &nbsp;|&nbsp;
  ⏭ Skipped: <b><?= $totalSkipped ?></b> &nbsp;|&nbsp;
  📵 No phone: <b><?= $totalNoPhone ?></b> &nbsp;|&nbsp;
  ❌ Not enrolled: <b><?= $totalNotIn ?></b>
</div>
<div class="box" style="background:#e3f2fd;border:1px solid #90caf9;margin-top:8px;font-size:15px">
  ⏳ Next batch starts in <b id="cd">3</b> seconds…
  <a href="<?= $nextUrl ?>" style="margin-left:16px;color:#1565c0">Skip wait ▶</a>
</div>
<script>
  var s = 3;
  var t = setInterval(function(){
    s--;
    document.getElementById('cd').textContent = s;
    if(s <= 0){ clearInterval(t); window.location = '<?= $nextUrl ?>'; }
  }, 1000);
</script>
<?php else: ?>
<div class="done">
  🎉 <b>All done!</b><br><br>
  Final totals:<br>
  ✅ Points awarded: <b><?= $totalProcessed ?></b> payments<br>
  ⏭ Already processed: <b><?= $totalSkipped ?></b><br>
  📵 No phone on order: <b><?= $totalNoPhone ?></b><br>
  ❌ Phone not enrolled: <b><?= $totalNotIn ?></b><br><br>
  <small>Delete clover_backfill_points.php from your server.</small>
</div>
<?php endif; ?>

</body></html>
