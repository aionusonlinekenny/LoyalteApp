<?php
// Upload a CSV of Clover customer points → merge into loyalty database
// CSV columns (any order, detected by header row):
//   phone / phoneNumber / Phone
//   points / Points / balance / Balance / reward_points
//   name / Name / customerName (optional)
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: text/html; charset=utf-8');
$db   = get_db();
$nowMs = (int)(microtime(true) * 1000);

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    $tmp = $_FILES['csvfile']['tmp_name'];
    if (!$tmp || !is_readable($tmp)) {
        $result = ['error' => 'Could not read uploaded file.'];
    } else {
        $handle = fopen($tmp, 'r');
        $header = fgetcsv($handle);
        if (!$header) { $result = ['error' => 'Empty CSV.']; goto done; }

        // Normalise header names
        $header = array_map('trim', $header);
        $lower  = array_map('strtolower', $header);

        $phoneCol  = array_search('phone', $lower)
                  ?? array_search('phonenumber', $lower)
                  ?? array_search('phone number', $lower)
                  ?? array_search('mobile', $lower)
                  ?? false;
        $pointsCol = array_search('points', $lower)
                  ?? array_search('balance', $lower)
                  ?? array_search('reward_points', $lower)
                  ?? array_search('rewardpoints', $lower)
                  ?? array_search('loyalty points', $lower)
                  ?? false;
        $nameCol   = array_search('name', $lower)
                  ?? array_search('customername', $lower)
                  ?? array_search('customer name', $lower)
                  ?? false;

        if ($phoneCol === false || $pointsCol === false) {
            $result = ['error' => 'CSV must have a "phone" column and a "points" (or "balance") column. Found headers: ' . implode(', ', $header)];
            fclose($handle);
            goto done;
        }

        $imported = $notFound = $zeroSkip = $alreadyDone = 0;
        $rows = '';
        $mode = $_POST['mode'] ?? 'add';   // 'add' or 'replace'

        while (($row = fgetcsv($handle)) !== false) {
            $rawPhone = preg_replace('/\D/', '', $row[$phoneCol] ?? '');
            $pts      = (int)($row[$pointsCol] ?? 0);
            $csvName  = $nameCol !== false ? trim($row[$nameCol] ?? '') : '';

            if (!$rawPhone) continue;
            if ($pts <= 0 && $mode === 'add') {
                $zeroSkip++;
                $rows .= "<tr><td>" . htmlspecialchars($rawPhone) . "</td><td style='color:#aaa'>0</td><td style='color:#aaa'>Skipped (0 pts)</td></tr>";
                continue;
            }

            // Match loyalty customer (strip leading 1 for US numbers)
            $stmt = $db->prepare('SELECT id, name, points FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
            $stmt->execute([$rawPhone, ltrim($rawPhone, '1')]);
            $cust = $stmt->fetch();

            if (!$cust) {
                $notFound++;
                $rows .= "<tr><td>" . htmlspecialchars($rawPhone) . "</td><td>$pts</td><td style='color:orange'>Not in loyalty DB</td></tr>";
                continue;
            }

            if ($mode === 'replace') {
                $newPts = $pts;
            } else {
                // 'add' mode — skip if they already have points (assume already imported)
                if ($cust['points'] >= $pts) {
                    $alreadyDone++;
                    $rows .= "<tr><td>" . htmlspecialchars($cust['name']) . "</td><td>$pts</td><td style='color:#888'>Already has " . $cust['points'] . " pts — skipped</td></tr>";
                    continue;
                }
                $newPts = $cust['points'] + $pts;
            }

            $tier = tier_from_points($newPts);
            $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')
               ->execute([$newPts, $tier, $nowMs, $cust['id']]);
            $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
               ->execute([uuid4(), $cust['id'], $pts, 'Clover CSV import ' . date('Y-m-d'), $nowMs]);

            $imported++;
            $color = $newPts > 0 ? 'green' : '#888';
            $rows .= "<tr><td>" . htmlspecialchars($cust['name']) . "</td><td>$pts</td><td style='color:$color'>✅ Now <b>$newPts</b> pts ($tier)</td></tr>";
        }
        fclose($handle);

        $result = compact('imported', 'notFound', 'zeroSkip', 'alreadyDone', 'rows', 'mode');
    }
    done:
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Points CSV Import</title>
<style>
  body{font-family:sans-serif;padding:24px;max-width:900px}
  h2{margin-bottom:4px}
  label{display:block;margin-top:12px;font-weight:bold;font-size:14px}
  input[type=file]{margin-top:6px}
  select{padding:5px 10px;font-size:14px}
  button{padding:10px 26px;background:#1565c0;color:#fff;border:none;border-radius:6px;font-size:15px;cursor:pointer;margin-top:16px}
  button:hover{background:#0d47a1}
  table{width:100%;border-collapse:collapse;margin-top:12px}
  th,td{padding:7px 11px;border:1px solid #ddd;font-size:13px;text-align:left}
  th{background:#f5f5f5}
  .box{padding:13px 18px;border-radius:8px;margin:12px 0;font-size:14px}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7}
  .warn{background:#fff8e1;border:1px solid #ffe082}
  .err{background:#ffebee;border:1px solid #ef9a9a}
  .info{background:#e3f2fd;border:1px solid #90caf9}
  code{background:#f5f5f5;padding:2px 6px;border-radius:3px;font-size:12px}
</style>
</head><body>
<h2>Clover Points CSV Import</h2>
<p style="color:#666;margin-top:2px">Upload a CSV with customer phone numbers and their Clover Rewards point balances.</p>

<div class="box info">
  <b>Expected CSV format</b> (header row required):<br><br>
  <code>phone,points,name</code><br>
  <code>2295551234,450,John Smith</code><br>
  <code>12295559876,120,Jane Doe</code><br><br>
  Accepted column names: <code>phone</code> / <code>phoneNumber</code> / <code>mobile</code> &nbsp;·&nbsp;
  <code>points</code> / <code>balance</code> / <code>reward_points</code> &nbsp;·&nbsp;
  <code>name</code> (optional)<br><br>
  <b>How to get this CSV:</b>
  <ul style="margin:6px 0">
    <li>Ask Clover Support (support@clover.com) for a loyalty point export for merchant ID <b>GW3XFCV71AK81</b></li>
    <li>Or copy data from the Clover Rewards History tab manually into a spreadsheet</li>
    <li>Or use the browser DevTools instructions in <code>clover_loyalty_probe.php</code></li>
  </ul>
</div>

<?php if ($result): ?>
  <?php if (isset($result['error'])): ?>
    <div class="box err">❌ <?= htmlspecialchars($result['error']) ?></div>
  <?php else: ?>
    <div class="box ok">
      <b>Import complete (<?= $result['mode'] === 'replace' ? 'replace mode' : 'add mode' ?>):</b><br>
      ✅ Updated: <b><?= $result['imported'] ?></b> &nbsp;|&nbsp;
      ⏭ Already had enough points: <b><?= $result['alreadyDone'] ?></b> &nbsp;|&nbsp;
      ❌ Not in loyalty DB: <b><?= $result['notFound'] ?></b> &nbsp;|&nbsp;
      〰 Zero pts skipped: <b><?= $result['zeroSkip'] ?></b>
    </div>
    <table>
      <thead><tr><th>Customer</th><th>CSV Points</th><th>Result</th></tr></thead>
      <tbody><?= $result['rows'] ?: '<tr><td colspan="3" style="text-align:center;color:#aaa">Nothing processed</td></tr>' ?></tbody>
    </table>
  <?php endif; ?>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" style="margin-top:24px">
  <label>CSV File</label>
  <input type="file" name="csvfile" accept=".csv,text/csv" required>

  <label style="margin-top:16px">Import mode</label>
  <select name="mode">
    <option value="add">Add to existing points (safe — recommended for first import)</option>
    <option value="replace">Replace / overwrite existing points (use only if you want to reset)</option>
  </select>

  <button type="submit">⬆ Upload &amp; Import Points</button>
</form>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_import_points_csv.php from server after use.</p>
</body></html>
