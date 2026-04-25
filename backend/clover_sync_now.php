<?php
// One-click Clover sync + manual points award
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: text/html; charset=utf-8');

$db  = get_db();
$cfg = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
$token        = $cfg['access_token'] ?? '';
$mId          = $cfg['merchant_id']  ?? '';
$env          = $cfg['environment']  ?? 'sandbox';
$ptsPerDollar = max(1, (int)($cfg['points_per_dollar'] ?? 1));
$base         = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
$nowMs        = (int)(microtime(true) * 1000);

// ── Helper: extract phone from Clover customer element ─────────────────────
function extract_phone(array $cc): string {
    $raw = preg_replace('/\D/', '', $cc['phoneNumber'] ?? '');
    if (!$raw) {
        foreach ($cc['phoneNumbers']['elements'] ?? [] as $pn) {
            $raw = preg_replace('/\D/', '', $pn['phoneNumber'] ?? '');
            if ($raw) break;
        }
    }
    return $raw;
}

// ── Helper: GET from Clover API ────────────────────────────────────────────
function clover_get(string $base, string $token, string $path): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>12,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code'=>$code, 'data'=>($body ? json_decode($body, true) : null), 'raw'=>$body];
}

// ── Action: delete failed log so payment can be re-synced ─────────────────
if (isset($_GET['retry_payment'])) {
    $pid = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['retry_payment']));
    if ($pid) $db->prepare("DELETE FROM clover_payment_logs WHERE payment_id=? AND status IN ('no_customer','error')")->execute([$pid]);
    header('Location: clover_sync_now.php'); exit;
}

// ── Action: reset last_sync_at ────────────────────────────────────────────
if (isset($_GET['reset_hours'])) {
    $h = max(1, min(72, (int)$_GET['reset_hours']));
    $ts = $nowMs - ($h * 3600 * 1000);
    $db->prepare("INSERT INTO clover_config (config_key,config_val,updated_at) VALUES ('last_sync_at',?,?) ON DUPLICATE KEY UPDATE config_val=VALUES(config_val),updated_at=VALUES(updated_at)")->execute([$ts,$ts]);
    header('Location: clover_sync_now.php'); exit;
}

// ── Action: manual award points to a customer ──────────────────────────────
$manualMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_phone'])) {
    $manualPhone = preg_replace('/\D/', '', $_POST['manual_phone'] ?? '');
    $manualPts   = max(0, (int)($_POST['manual_pts'] ?? 0));
    $manualNote  = trim($_POST['manual_note'] ?? 'Manual staff award');
    $manualNote  = htmlspecialchars($manualNote);

    if ($manualPhone && $manualPts > 0) {
        $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone=? OR phone=? LIMIT 1');
        $stmt->execute([$manualPhone, ltrim($manualPhone, '1')]);
        $cust = $stmt->fetch();

        if (!$cust) {
            // Auto-create if not found
            $cust = find_or_create_loyalty_customer($db, $manualPhone, '', $nowMs);
            $manualMsg = "<div class='box ok'>✅ Created new customer for $manualPhone and awarded <b>$manualPts pts</b>.</div>";
        } else {
            $manualMsg = "<div class='box ok'>✅ Awarded <b>$manualPts pts</b> to $manualPhone.</div>";
        }

        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT points FROM customers WHERE id=? FOR UPDATE');
            $s->execute([$cust['id']]);
            $cur = $s->fetch();
            $newPts = $cur['points'] + $manualPts;
            $tier   = tier_from_points($newPts);
            $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')->execute([$newPts,$tier,$nowMs,$cust['id']]);
            $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')->execute([uuid4(),$cust['id'],$manualPts,$manualNote,$nowMs]);
            $db->commit();
            $manualMsg = "<div class='box ok'>✅ Awarded <b>$manualPts pts</b> to $manualPhone — now has <b>$newPts pts</b> ($tier).</div>";
        } catch (Exception $e) {
            $db->rollBack();
            $manualMsg = "<div class='box err'>❌ DB error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $manualMsg = "<div class='box err'>❌ Enter a valid phone and points > 0.</div>";
    }
}

// ── Main sync ─────────────────────────────────────────────────────────────
if (!$token || !$mId) {
    die('<h2 style="color:red">❌ No access token or merchant ID. Go to Admin → Loyalty → Clover POS and save settings first.</h2>');
}

$lastSync = (int)($cfg['last_sync_at'] ?? 0);
if (!$lastSync) $lastSync = $nowMs - (24 * 60 * 60 * 1000);

$r = clover_get($base, $token, "/v3/merchants/{$mId}/payments?filter=createdTime%3E{$lastSync}&expand=order&limit=100");
if ($r['code'] !== 200) {
    die("<h2 style='color:red'>❌ Clover API error (HTTP {$r['code']})</h2><pre>" . htmlspecialchars($r['raw']) . "</pre>");
}

$payments  = $r['data']['elements'] ?? [];
$processed = $skipped = $noPhone = 0;
$rows = '';

foreach ($payments as $payment) {
    $paymentId   = $payment['id'] ?? '';
    $amountCents = (int)($payment['amount'] ?? 0);
    $orderId     = $payment['order']['id'] ?? null;
    if (!$paymentId) continue;

    $check = $db->prepare('SELECT id FROM clover_payment_logs WHERE payment_id=?');
    $check->execute([$paymentId]);
    if ($check->fetch()) {
        $skipped++;
        $rows .= "<tr><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:gray'>Already processed</td><td>—</td></tr>";
        continue;
    }

    $phone = null;
    $ord   = null;

    // ── Step 1: try order.customers (the usual path) ──────────────────────
    if ($orderId) {
        $r2 = clover_get($base, $token, "/v3/merchants/{$mId}/orders/{$orderId}?expand=customers,customers.phoneNumbers");
        $ord = $r2['data'];
        foreach ($ord['customers']['elements'] ?? [] as $cc) {
            $raw = extract_phone($cc);
            if ($raw) { $phone = $raw; break; }
        }
    }

    // ── Step 2: try payment.customer (Clover sometimes attaches here) ─────
    if (!$phone) {
        $r3 = clover_get($base, $token, "/v3/merchants/{$mId}/payments/{$paymentId}?expand=customer,customer.phoneNumbers");
        $pmtData = $r3['data'];
        if (!empty($pmtData['customer'])) {
            $raw = extract_phone($pmtData['customer']);
            if ($raw) $phone = $raw;
        }
    }

    if (!$phone) {
        $db->prepare('INSERT INTO clover_payment_logs (payment_id,merchant_id,order_id,amount_cents,points_awarded,status,note,created_at) VALUES (?,?,?,?,0,"no_customer","No phone — award manually",?)')->execute([$paymentId,$mId,$orderId,$amountCents,$nowMs]);
        $noPhone++;
        $ordNote = htmlspecialchars($ord['note'] ?? '');
        $pts4 = intdiv($amountCents, 100) * $ptsPerDollar;
        $rows .= "<tr style='background:#fff8e1'>"
            . "<td>$paymentId</td>"
            . "<td>$" . number_format($amountCents/100,2) . "</td>"
            . "<td style='color:orange'>No phone attached in Clover"
            . ($ordNote ? " <small>($ordNote)</small>" : '')
            . "</td>"
            . "<td><a href='#manual' onclick=\"document.getElementById('mphone').value='';document.getElementById('mpts').value='$pts4';document.getElementById('mnote').value='Clover payment $paymentId \$" . number_format($amountCents/100,2) . "';\" style='background:#e65100;color:#fff;padding:2px 8px;border-radius:4px;text-decoration:none;font-size:12px'>Award manually ↓</a></td>"
            . "</tr>";
        continue;
    }

    $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone=? OR phone=? LIMIT 1');
    $stmt->execute([$phone, ltrim($phone,'1')]);
    $customer = $stmt->fetch();

    if (!$customer) {
        $customer = find_or_create_loyalty_customer($db, $phone, '', $nowMs);
    }

    $pts = intdiv($amountCents, 100) * $ptsPerDollar;
    if ($pts > 0) {
        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT points FROM customers WHERE id=? FOR UPDATE');
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
    $rows .= "<tr style='background:#f1f8e9'><td>$paymentId</td><td>$" . number_format($amountCents/100,2) . "</td><td style='color:green'>✅ +{$pts} pts → $phone</td><td>$pts</td></tr>";
}

$db->prepare("INSERT INTO clover_config (config_key,config_val,updated_at) VALUES ('last_sync_at',?,?) ON DUPLICATE KEY UPDATE config_val=VALUES(config_val),updated_at=VALUES(updated_at)")->execute([$nowMs,$nowMs]);
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Sync</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:960px}
h2,h3{margin-bottom:6px}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{padding:8px 12px;border:1px solid #ddd;text-align:left;vertical-align:middle}
th{background:#f5f5f5}
.box{padding:14px 18px;border-radius:8px;margin-bottom:12px;font-size:14px}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
.warn{background:#fff3e0;border:1px solid #ffb74d}
.err{background:#ffebee;border:1px solid #ef9a9a}
.info{background:#e3f2fd;border:1px solid #90caf9}
label{display:block;font-weight:bold;font-size:13px;margin-top:10px}
input[type=text],input[type=number]{padding:7px 10px;border:1px solid #ccc;border-radius:5px;font-size:14px;width:220px}
button{padding:9px 22px;border:none;border-radius:6px;font-size:14px;cursor:pointer;margin-top:12px;background:#1565c0;color:#fff}
button:hover{background:#0d47a1}
a.btn{display:inline-block;padding:5px 12px;border-radius:4px;text-decoration:none;font-size:13px;color:#fff;background:#4caf50;margin-right:4px}
a.btn-blue{background:#1565c0}a.btn-red{background:#e65100}
</style>
</head><body>
<h2>Clover Payment Sync — <?= date('Y-m-d H:i:s') ?></h2>

<div class="box ok">
  <b>Total payments fetched:</b> <?= count($payments) ?><br>
  <b style="color:green">✅ Points awarded:</b> <?= $processed ?> payments<br>
  <b style="color:gray">⏭ Already processed:</b> <?= $skipped ?><br>
  <b style="color:orange">📵 No phone in Clover:</b> <?= $noPhone ?>
</div>

<?php if ($noPhone > 0): ?>
<div class="box warn">
  ⚠️ <b><?= $noPhone ?> payment(s) had no phone attached in Clover.</b><br>
  This happens when a customer enters their phone for <i>Clover's own</i> loyalty — that data is not in Clover's standard API.<br>
  Use the <b>Manual Award</b> form below to credit these customers directly.
</div>
<?php endif; ?>

<div style="margin-bottom:12px">
  <a class="btn" href="clover_sync_now.php">↻ Re-sync</a>
  <a class="btn btn-blue" href="?reset_hours=6">Reset to last 6h</a>
  <a class="btn btn-blue" href="?reset_hours=24">Reset to last 24h</a>
  <a class="btn btn-blue" href="?reset_hours=48">Reset to last 48h</a>
</div>

<table>
  <thead><tr><th>Payment ID</th><th>Amount</th><th>Result</th><th>Points</th></tr></thead>
  <tbody><?= $rows ?: '<tr><td colspan="4" style="text-align:center;color:gray">No new payments</td></tr>' ?></tbody>
</table>

<!-- ── Manual Award ──────────────────────────────────────────────────────── -->
<h3 id="manual" style="margin-top:36px">Manual Points Award</h3>
<div class="box info">
  Use this when Clover doesn't attach a phone to the order (e.g. Clover Rewards customers).<br>
  Enter the customer's phone number and how many points to award. The system will find or create their account.
</div>

<?= $manualMsg ?>

<form method="POST" action="#manual">
  <label>Customer Phone Number</label>
  <input type="text" id="mphone" name="manual_phone" placeholder="e.g. 2295903009" required>

  <label>Points to Award</label>
  <input type="number" id="mpts" name="manual_pts" min="1" value="1" required>

  <label>Note (appears in transaction history)</label>
  <input type="text" id="mnote" name="manual_note" value="Manual staff award" style="width:380px">

  <br><button type="submit">⭐ Award Points</button>
</form>

<p style="color:#bbb;margin-top:40px;font-size:12px">Sync window resets each run. Use "Reset to last Xh" if you need to re-fetch older payments.</p>
</body></html>
