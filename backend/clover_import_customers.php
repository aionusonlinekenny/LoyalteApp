<?php
// Import Clover customers page by page — DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: text/html; charset=utf-8');

$db  = get_db();
$cfg = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);

$token = $cfg['access_token'] ?? '';
$mId   = $cfg['merchant_id']  ?? '';
$env   = $cfg['environment']  ?? 'sandbox';

if (!$token || !$mId) {
    die('<h2 style="color:red">❌ No access token or merchant ID saved yet.</h2>');
}

$base   = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
$nowMs  = (int)(microtime(true) * 1000);
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = 100;

// Fetch one page of customers
$url = $base . "/v3/merchants/{$mId}/customers?expand=phoneNumbers,emailAddresses&limit={$limit}&offset={$offset}";
$ch  = curl_init($url);
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

$page     = json_decode($body, true);
$elements = $page['elements'] ?? [];
$hasMore  = count($elements) === $limit;

$imported = $skipped = $noPhone = 0;
$rows = '';

foreach ($elements as $cc) {
    $firstName = trim($cc['firstName'] ?? '');
    $lastName  = trim($cc['lastName']  ?? '');
    $name      = trim("$firstName $lastName") ?: 'Unknown';

    $phones   = $cc['phoneNumbers']['elements'] ?? [];
    $rawPhone = '';
    foreach ($phones as $p) {
        $rawPhone = preg_replace('/\D/', '', $p['phoneNumber'] ?? '');
        if ($rawPhone) break;
    }

    $emails = $cc['emailAddresses']['elements'] ?? [];
    $email  = null;
    foreach ($emails as $e) {
        if (!empty($e['emailAddress'])) { $email = $e['emailAddress']; break; }
    }

    if (!$rawPhone) {
        $noPhone++;
        $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td style='color:#aaa'>—</td><td style='color:#aaa'>No phone</td></tr>";
        continue;
    }

    $exists = $db->prepare('SELECT id, member_id FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
    $exists->execute([$rawPhone, ltrim($rawPhone, '1')]);
    $existing = $exists->fetch();

    if ($existing) {
        $skipped++;
        $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td>$rawPhone</td><td style='color:gray'>⏭ Already exists ({$existing['member_id']})</td></tr>";
        continue;
    }

    $row   = $db->query("SELECT MAX(CAST(SUBSTRING(member_id,5) AS UNSIGNED)) AS mx FROM customers")->fetch();
    $seq   = (int)($row['mx'] ?? 0) + 1;
    $memId = sprintf('LYL-%06d', $seq);
    $cid   = uuid4();

    $db->prepare(
        'INSERT INTO customers (id,member_id,name,phone,email,tier,points,qr_code,created_at,updated_at)
         VALUES (?,?,?,?,?,\'BRONZE\',0,?,?,?)'
    )->execute([$cid, $memId, $name, $rawPhone, $email, $memId, $nowMs, $nowMs]);

    $imported++;
    $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td>$rawPhone</td><td style='color:green'>✅ Imported — $memId</td></tr>";
}

$nextOffset = $offset + $limit;
$currentEnd = $offset + count($elements);
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Import</title>
<style>
  body{font-family:sans-serif;padding:24px;max-width:860px}
  table{width:100%;border-collapse:collapse;margin-top:16px}
  th,td{padding:8px 12px;border:1px solid #ddd;text-align:left;font-size:14px}
  th{background:#f5f5f5}
  .box{padding:14px 18px;border-radius:8px;margin-bottom:14px;font-size:14px}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7}
  .btn{display:inline-block;padding:12px 28px;background:#2e7d32;color:white;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;margin-top:16px}
  .btn:hover{background:#1b5e20}
  .done{background:#1565c0;color:white;padding:14px 18px;border-radius:8px;margin-top:16px}
</style>
</head><body>

<h2>Clover Customer Import</h2>
<p style="color:#666">Processing customers <?= $offset + 1 ?> – <?= $currentEnd ?></p>

<div class="box ok">
  <strong>This batch (<?= count($elements) ?> customers):</strong><br>
  ✅ Imported: <strong><?= $imported ?></strong> &nbsp;|&nbsp;
  ⏭ Already exists: <strong><?= $skipped ?></strong> &nbsp;|&nbsp;
  📵 No phone: <strong><?= $noPhone ?></strong>
</div>

<table>
  <thead><tr><th>Name</th><th>Phone</th><th>Result</th></tr></thead>
  <tbody>
    <?= $rows ?: '<tr><td colspan="3" style="text-align:center;color:gray">No customers in this batch</td></tr>' ?>
  </tbody>
</table>

<?php if ($hasMore): ?>
  <a class="btn" href="?offset=<?= $nextOffset ?>">
    ▶ Next 100 customers (<?= $nextOffset ?>+)
  </a>
  <p style="color:#888;font-size:13px;margin-top:8px">Click the button to continue importing the next batch.</p>
<?php else: ?>
  <div class="done">
    🎉 <strong>All done!</strong> No more customers to import.<br>
    <span style="font-size:13px;opacity:0.85">Remember to delete this file from your server.</span>
  </div>
<?php endif; ?>

<p style="color:#bbb;margin-top:24px;font-size:12px">⚠️ Delete clover_import_customers.php from server after use.</p>
</body></html>
