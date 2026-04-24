<?php
// Backfill points via Clover customer profiles (not order phone lookup)
// Goes customer by customer → fetches their orders → awards points
// DELETE after use!
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

$offset  = max(0, (int)($_GET['offset'] ?? 0));
$days    = max(1, (int)($_GET['days']   ?? 365));
$limit   = 50; // customers per batch
$fromMs  = $nowMs - ($days * 24 * 60 * 60 * 1000);

// Cumulative totals passed through URL
$totalProcessed = (int)($_GET['tp']  ?? 0);
$totalSkipped   = (int)($_GET['ts']  ?? 0);
$totalNoMatch   = (int)($_GET['tnm'] ?? 0);
$totalCustomers = (int)($_GET['tc']  ?? 0);

// ── Parallel curl ─────────────────────────────────────────────────────────────
function fetch_parallel(string $base, string $token, array $paths, int $concurrency = 15): array {
    $results = [];
    foreach (array_chunk($paths, $concurrency, true) as $chunk) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($chunk as $key => $path) {
            $ch = curl_init($base . $path);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
                CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
            $handles[$key] = $ch;
            curl_multi_add_handle($mh, $ch);
        }
        do { $status = curl_multi_exec($mh, $active); if ($active) curl_multi_select($mh); }
        while ($active && $status == CURLM_OK);
        foreach ($handles as $key => $ch) {
            $body = curl_multi_getcontent($ch);
            $results[$key] = $body ? json_decode($body, true) : null;
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);
    }
    return $results;
}

function clover_get(string $base, string $token, string $path): ?array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200 && $body) ? json_decode($body, true) : null;
}

// ── Step 1: Fetch batch of Clover customers ───────────────────────────────────
$custData = clover_get($base, $token,
    "/v3/merchants/{$mId}/customers?expand=phoneNumbers,emailAddresses&limit={$limit}&offset={$offset}");

if (!$custData) die("<h2 style='color:red'>❌ Failed to fetch customers from Clover API.</h2>");

$cloverCustomers = $custData['elements'] ?? [];
$hasMore         = count($cloverCustomers) === $limit;

// ── Step 2: Fetch orders for each customer in parallel ────────────────────────
$orderPaths = [];
foreach ($cloverCustomers as $i => $cc) {
    $orderPaths[$i] = "/v3/merchants/{$mId}/customers/{$cc['id']}/orders"
                    . "?filter=clientCreatedTime%3E{$fromMs}&expand=payments&limit=200";
}
$allOrders = fetch_parallel($base, $token, $orderPaths, 15);

// ── Step 3: Process each customer ─────────────────────────────────────────────
$processed = $skipped = $noMatch = 0;
$rows = '';

foreach ($cloverCustomers as $i => $cc) {
    $firstName = trim($cc['firstName'] ?? '');
    $lastName  = trim($cc['lastName']  ?? '');
    $custName  = trim("$firstName $lastName") ?: 'Unknown';

    // Get phone
    $rawPhone = '';
    foreach ($cc['phoneNumbers']['elements'] ?? [] as $p) {
        $rawPhone = preg_replace('/\D/', '', $p['phoneNumber'] ?? '');
        if ($rawPhone) break;
    }

    if (!$rawPhone) {
        $noMatch++;
        $rows .= "<tr><td>" . htmlspecialchars($custName) . "</td><td style='color:#bbb'>No phone</td><td style='color:#bbb'>—</td><td>0</td></tr>";
        continue;
    }

    // Match loyalty customer
    $stmt = $db->prepare('SELECT id, name, points FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
    $stmt->execute([$rawPhone, ltrim($rawPhone, '1')]);
    $loyalCustomer = $stmt->fetch();

    if (!$loyalCustomer) {
        $noMatch++;
        $rows .= "<tr><td>" . htmlspecialchars($custName) . "</td><td>$rawPhone</td><td style='color:orange'>Not in loyalty DB</td><td>0</td></tr>";
        continue;
    }

    // Process their orders
    $orders    = $allOrders[$i]['elements'] ?? [];
    $custPts   = 0;
    $custDone  = 0;
    $custSkip  = 0;

    foreach ($orders as $order) {
        foreach ($order['payments']['elements'] ?? [] as $payment) {
            $paymentId   = $payment['id']          ?? '';
            $amountCents = (int)($payment['amount'] ?? 0);
            if (!$paymentId || $amountCents <= 0) continue;

            // Skip already processed
            $check = $db->prepare('SELECT id FROM clover_payment_logs WHERE payment_id = ?');
            $check->execute([$paymentId]);
            if ($check->fetch()) { $custSkip++; $skipped++; continue; }

            $pts = intdiv($amountCents, 100) * $ptsPerDollar;

            if ($pts > 0) {
                $db->beginTransaction();
                try {
                    $s = $db->prepare('SELECT points FROM customers WHERE id = ? FOR UPDATE');
                    $s->execute([$loyalCustomer['id']]);
                    $cur       = $s->fetch();
                    $newPoints = $cur['points'] + $pts;
                    $tier      = tier_from_points($newPoints);
                    $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')
                       ->execute([$newPoints, $tier, $nowMs, $loyalCustomer['id']]);
                    $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
                       ->execute([uuid4(), $loyalCustomer['id'], $pts, "Clover customer-backfill $paymentId", $nowMs]);
                    $db->commit();
                    $custPts += $pts;
                } catch (Exception $e) { $db->rollBack(); $pts = 0; }
            }

            $db->prepare(
                'INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,customer_id,phone,amount_cents,points_awarded,status,created_at)
                 VALUES (?,?,?,?,?,?,?,"processed",?)'
            )->execute([$paymentId, $mId, $order['id'] ?? null, $loyalCustomer['id'], $rawPhone, $amountCents, $pts, $nowMs]);

            $custDone++;
            $processed++;
        }
    }

    $n = htmlspecialchars($loyalCustomer['name']);
    $color = $custPts > 0 ? 'green' : '#888';
    $detail = $custPts > 0 ? "+{$custPts} pts ({$custDone} payments)" : ($custSkip > 0 ? "All {$custSkip} already done" : "No payments found");
    $rows .= "<tr><td>$n</td><td>$rawPhone</td><td style='color:$color'>$detail</td><td><b>$custPts</b></td></tr>";
    $totalCustomers++;
}

$nextOffset    = $offset + $limit;
$totalProcessed += $processed;
$totalSkipped   += $skipped;
$totalNoMatch   += $noMatch;
$nextUrl = "?offset={$nextOffset}&days={$days}&tp={$totalProcessed}&ts={$totalSkipped}&tnm={$totalNoMatch}&tc={$totalCustomers}";
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Customer Backfill</title>
<style>
  body{font-family:sans-serif;padding:24px;max-width:1000px}
  h2{margin-bottom:4px}
  table{width:100%;border-collapse:collapse;margin-top:12px}
  th,td{padding:7px 11px;border:1px solid #ddd;font-size:13px;text-align:left}
  th{background:#f5f5f5}
  .box{padding:13px 18px;border-radius:8px;margin-bottom:10px;font-size:14px}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7}
  .info{background:#e3f2fd;border:1px solid #90caf9}
  .warn{background:#fff8e1;border:1px solid #ffe082}
  .done{background:#2e7d32;color:white;padding:18px 22px;border-radius:8px;margin-top:16px}
  a.day{margin-right:10px;color:#1565c0}
</style>
</head><body>
<h2>Clover Customer-Based Backfill</h2>
<p style="color:#666;margin-top:2px">Fetching orders per customer — last <?= $days ?> days</p>

<?php if ($offset === 0): ?>
<div class="box info">
  📅 Period: <strong><?= date('M d, Y', $fromMs/1000) ?></strong> → now &nbsp;|&nbsp;
  Change: <a class="day" href="?days=30">30d</a><a class="day" href="?days=90">90d</a><a class="day" href="?days=180">6mo</a><a class="day" href="?days=365">1yr</a>
</div>
<?php endif; ?>

<div class="box ok">
  <b>Batch <?= floor($offset/$limit)+1 ?> — customers <?= $offset+1 ?>–<?= $offset+count($cloverCustomers) ?>:</b><br>
  ✅ Payments processed: <b><?= $processed ?></b> &nbsp;|&nbsp;
  ⏭ Already done: <b><?= $skipped ?></b> &nbsp;|&nbsp;
  ❌ No loyalty match: <b><?= $noMatch ?></b>
</div>

<div class="box warn">
  <b>Running totals — <?= $totalCustomers ?> customers so far:</b><br>
  ✅ Total payments awarded: <b><?= $totalProcessed ?></b> &nbsp;|&nbsp;
  ⏭ Skipped: <b><?= $totalSkipped ?></b> &nbsp;|&nbsp;
  ❌ No match: <b><?= $totalNoMatch ?></b>
</div>

<table>
  <thead><tr><th>Customer</th><th>Phone</th><th>Result</th><th>Points Added</th></tr></thead>
  <tbody>
    <?= $rows ?: '<tr><td colspan="4" style="text-align:center;color:#aaa">No customers in this batch</td></tr>' ?>
  </tbody>
</table>

<?php if ($hasMore): ?>
<div class="box info" style="margin-top:16px">
  ⏳ Next batch in <b id="cd">3</b>s…
  <a href="<?= $nextUrl ?>" style="margin-left:16px;color:#1565c0;font-weight:bold">Skip ▶</a>
</div>
<script>
var s=3, t=setInterval(function(){
  document.getElementById('cd').textContent=--s;
  if(s<=0){clearInterval(t);window.location='<?= $nextUrl ?>';}
},1000);
</script>
<?php else: ?>
<div class="done">
  🎉 <b>All customers processed!</b><br><br>
  ✅ Total payments awarded: <b><?= $totalProcessed ?></b><br>
  ⏭ Already processed: <b><?= $totalSkipped ?></b><br>
  ❌ No loyalty match: <b><?= $totalNoMatch ?></b><br><br>
  <small>Delete clover_backfill_points.php from your server.</small>
</div>
<?php endif; ?>
</body></html>
