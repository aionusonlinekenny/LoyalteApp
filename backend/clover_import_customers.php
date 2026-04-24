<?php
// Import Clover customers into loyalty database
// Open in browser to run — DELETE after use!
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
    die('<h2 style="color:red">❌ No access token or merchant ID.<br>Go to Admin → Loyalty → Clover POS and save settings first.</h2>');
}

$base    = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
$nowMs   = (int)(microtime(true) * 1000);
$allCust = [];

// Fetch all customers with pagination (100 per page)
$offset = 0;
do {
    $url = $base . "/v3/merchants/{$mId}/customers?expand=phoneNumbers,emailAddresses&limit=100&offset={$offset}";
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
        die("<h2 style='color:red'>❌ Clover API error (HTTP $httpCode)</h2><pre>$body</pre>");
    }

    $page     = json_decode($body, true);
    $elements = $page['elements'] ?? [];
    $allCust  = array_merge($allCust, $elements);
    $offset  += count($elements);
} while (count($elements) === 100);

// Process each customer
$imported = $skipped = $noPhone = 0;
$rows = '';

foreach ($allCust as $cc) {
    $firstName = trim($cc['firstName'] ?? '');
    $lastName  = trim($cc['lastName']  ?? '');
    $name      = trim("$firstName $lastName") ?: 'Unknown';

    // Get phone
    $phones = $cc['phoneNumbers']['elements'] ?? [];
    $rawPhone = '';
    foreach ($phones as $p) {
        $rawPhone = preg_replace('/\D/', '', $p['phoneNumber'] ?? '');
        if ($rawPhone) break;
    }

    // Get email
    $emails   = $cc['emailAddresses']['elements'] ?? [];
    $email    = null;
    foreach ($emails as $e) {
        if (!empty($e['emailAddress'])) { $email = $e['emailAddress']; break; }
    }

    if (!$rawPhone) {
        $noPhone++;
        $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td style='color:gray'>No phone</td><td style='color:gray'>Skipped</td></tr>";
        continue;
    }

    // Check if already exists by phone
    $exists = $db->prepare('SELECT id, member_id FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
    $exists->execute([$rawPhone, ltrim($rawPhone, '1')]);
    $existing = $exists->fetch();

    if ($existing) {
        $skipped++;
        $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td>$rawPhone</td><td style='color:gray'>⏭ Already exists ({$existing['member_id']})</td></tr>";
        continue;
    }

    // Generate member_id
    $row    = $db->query("SELECT MAX(CAST(SUBSTRING(member_id,5) AS UNSIGNED)) AS mx FROM customers")->fetch();
    $seq    = (int)($row['mx'] ?? 0) + 1;
    $memId  = sprintf('LYL-%06d', $seq);
    $cid    = uuid4();
    $phone  = $rawPhone;

    $db->prepare(
        'INSERT INTO customers (id,member_id,name,phone,email,tier,points,qr_code,created_at,updated_at)
         VALUES (?,?,?,?,?,\'BRONZE\',0,?,?,?)'
    )->execute([$cid, $memId, $name, $phone, $email, $memId, $nowMs, $nowMs]);

    $imported++;
    $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td>$phone</td><td style='color:green'>✅ Imported as $memId</td></tr>";
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Customer Import</title>
<style>
  body{font-family:sans-serif;padding:30px;max-width:900px}
  table{width:100%;border-collapse:collapse;margin-top:20px}
  th,td{padding:8px 12px;border:1px solid #ddd;text-align:left}
  th{background:#f5f5f5}
  .box{padding:16px;border-radius:8px;margin-bottom:16px}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7}
  .warn{background:#fff3e0;border:1px solid #ffb74d}
</style>
</head><body>
<h2>Clover Customer Import — <?= date('Y-m-d H:i:s') ?></h2>

<div class="box ok">
  <strong>Total Clover customers found:</strong> <?= count($allCust) ?><br>
  <strong style="color:green">✅ Imported to loyalty:</strong> <?= $imported ?><br>
  <strong style="color:gray">⏭ Already in system:</strong> <?= $skipped ?><br>
  <strong style="color:orange">📵 No phone (skipped):</strong> <?= $noPhone ?>
</div>

<?php if ($noPhone > 0): ?>
<div class="box warn">
  💡 <strong><?= $noPhone ?> customers</strong> skipped because they have no phone number in Clover.
  Add their phone in Clover POS → Customers → Edit, then run this import again.
</div>
<?php endif; ?>

<table>
  <thead><tr><th>Name</th><th>Phone</th><th>Result</th></tr></thead>
  <tbody>
    <?= $rows ?: '<tr><td colspan="3" style="text-align:center;color:gray">No customers found in Clover</td></tr>' ?>
  </tbody>
</table>

<p style="color:#999;margin-top:30px">⚠️ Delete this file (clover_import_customers.php) from your server after use.</p>
</body></html>
