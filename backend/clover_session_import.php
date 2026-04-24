<?php
// Use a Clover WEB SESSION bearer token (from browser DevTools) to pull
// all loyalty/rewards customer point balances and import them.
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
header('Content-Type: text/html; charset=utf-8');

$db    = get_db();
$cfg   = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
$mId   = $cfg['merchant_id'] ?? '';
$env   = $cfg['environment'] ?? 'sandbox';
$base  = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';
$nowMs = (int)(microtime(true) * 1000);

if (!$mId) die('<h2 style="color:red">No merchant_id in config.</h2>');

// Session token can be submitted via form
$sessionToken = trim($_POST['session_token'] ?? $_GET['t'] ?? '');
$dryRun       = ($_POST['dry_run'] ?? '1') !== '0';
$appId        = trim($_POST['app_id'] ?? '1EVSVRM8SV8RC');  // Promos & Rewards client_id / app UUID

function sget(string $base, string $token, string $path): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => ($body ? json_decode($body, true) : null), 'raw' => $body];
}

$testResult = null;
$importResult = null;

if ($sessionToken) {
    // ── Step 1: probe key loyalty endpoints with this token ───────────────────
    $probes = [
        "/v3/loyalty/merchants/{$mId}/points",
        "/v3/loyalty/merchants/{$mId}/members?limit=5",
        "/v3/loyalty/merchants/{$mId}/schemes",
        "/v3/apps/{$appId}/merchants/{$mId}/customers?limit=3",
        "/v3/apps/{$appId}/merchants/{$mId}/members?limit=3",
        "/v3/apps/{$appId}/merchants/{$mId}/points",
        "/v3/merchants/{$mId}/customers?expand=customerAttributes&limit=3",
        "/v3/merchants/{$mId}/reward_cards?limit=3",
    ];

    $mh = curl_multi_init();
    $handles = [];
    foreach ($probes as $k => $path) {
        $ch = curl_init($base . $path);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>12,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$sessionToken,'Accept: application/json']]);
        $handles[$k] = $ch;
        curl_multi_add_handle($mh, $ch);
    }
    do { $s = curl_multi_exec($mh, $active); if ($active) curl_multi_select($mh); }
    while ($active && $s == CURLM_OK);
    $testResult = [];
    foreach ($handles as $k => $ch) {
        $body = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $testResult[$k] = ['path'=>$probes[$k], 'code'=>$code,
            'data'=>($body ? json_decode($body, true) : null), 'raw'=>$body];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    // ── Step 2: if a loyalty/members endpoint works, do the full import ───────
    $workingMembersPath = null;
    foreach ($testResult as $r) {
        if ($r['code'] === 200 && !empty($r['data']['elements'])) {
            // Check if elements have points-like fields
            $el = $r['data']['elements'][0] ?? [];
            if (isset($el['points']) || isset($el['balance']) || isset($el['loyaltyPoints'])) {
                $workingMembersPath = $r['path'];
                break;
            }
        }
    }

    if ($workingMembersPath && !$dryRun) {
        // Full import: paginate through all members
        $updated = $notFound = $total = 0;
        $rows = '';
        $offset = 0;
        $limit  = 100;

        do {
            $basePath = preg_replace('/\?.*/', '', $workingMembersPath);
            $r = sget($base, $sessionToken, $basePath . "?limit={$limit}&offset={$offset}");
            $members = $r['data']['elements'] ?? [];

            foreach ($members as $m) {
                $total++;
                $rawPhone = preg_replace('/\D/', '', $m['phone'] ?? $m['phoneNumber'] ?? '');
                $pts      = (int)($m['points'] ?? $m['balance'] ?? $m['loyaltyPoints'] ?? 0);
                $name     = trim(($m['firstName']??'') . ' ' . ($m['lastName']??'')) ?: ($m['name'] ?? '');

                if (!$rawPhone) { $notFound++; continue; }

                $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone=? OR phone=? LIMIT 1');
                $stmt->execute([$rawPhone, ltrim($rawPhone,'1')]);
                $cust = $stmt->fetch();

                if (!$cust) { $notFound++; $rows .= "<tr><td>".htmlspecialchars($rawPhone)."</td><td>$pts</td><td style='color:orange'>Not in loyalty DB</td></tr>"; continue; }

                $tier = tier_from_points($pts);
                $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')->execute([$pts,$tier,$nowMs,$cust['id']]);
                $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')->execute([uuid4(),$cust['id'],$pts,'Clover Rewards session import '.$nowMs,$nowMs]);
                $updated++;
                $rows .= "<tr><td>".htmlspecialchars($name ?: $rawPhone)."</td><td>$pts</td><td style='color:green'>✅ Set to <b>$pts</b> pts ($tier)</td></tr>";
            }

            $offset += $limit;
        } while (count($members) === $limit && $offset < 5000);

        $importResult = compact('updated','notFound','total','rows');
    }
}

function badge2(int $c): string {
    if ($c===200) return "<span style='background:#2e7d32;color:#fff;padding:2px 7px;border-radius:4px;font-weight:bold'>200 ✅</span>";
    if ($c===401) return "<span style='background:#e57373;color:#fff;padding:2px 6px;border-radius:4px'>401 Auth Error</span>";
    if ($c===403) return "<span style='background:#ef9a9a;color:#333;padding:2px 6px;border-radius:4px'>403 Forbidden</span>";
    if ($c===404) return "<span style='background:#bdbdbd;color:#333;padding:2px 6px;border-radius:4px'>404</span>";
    if ($c===405) return "<span style='background:#ffa726;color:#fff;padding:2px 6px;border-radius:4px'>405 No Method</span>";
    return "<span style='background:#90a4ae;color:#fff;padding:2px 6px;border-radius:4px'>$c</span>";
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Session Import</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:980px;background:#fafafa}
h2,h3{margin-bottom:4px}h3{color:#1565c0;margin-top:24px}
label{display:block;font-weight:bold;font-size:14px;margin-top:14px}
input[type=text],textarea{width:100%;padding:8px;font-size:13px;border:1px solid #ccc;border-radius:5px;box-sizing:border-box;font-family:monospace}
textarea{height:60px}
select{padding:6px 10px;font-size:14px}
button{padding:10px 26px;border:none;border-radius:6px;font-size:15px;cursor:pointer;margin-top:14px}
.btn-test{background:#1565c0;color:#fff}.btn-test:hover{background:#0d47a1}
.btn-import{background:#2e7d32;color:#fff}.btn-import:hover{background:#1b5e20}
.box{padding:13px 18px;border-radius:8px;margin-bottom:12px;font-size:14px}
.info{background:#e3f2fd;border:1px solid #90caf9}
.warn{background:#fff8e1;border:1px solid #ffe082}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
.err{background:#ffebee;border:1px solid #ef9a9a}
table{width:100%;border-collapse:collapse;margin-top:10px;font-size:13px}
th,td{padding:6px 10px;border:1px solid #ddd;vertical-align:top}th{background:#f0f0f0}
pre{margin:0;font-size:11px;max-height:160px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
code{background:#f0f0f0;padding:1px 5px;border-radius:3px;font-size:12px}
ol li{margin-bottom:8px;line-height:1.5}
</style>
</head><body>
<h2>Clover Rewards Session Import</h2>
<p style="color:#666">Use a browser session token (from DevTools) to pull loyalty points from Clover Rewards.</p>

<!-- ── DevTools instructions ─────────────────────────────────────────────── -->
<div class="box info">
  <b>📋 How to get your session bearer token (takes ~2 minutes):</b>
  <ol>
    <li>On your computer, open Chrome or Edge and go to:<br>
        <code>https://www.clover.com/rewards/overview?client_id=1EVSVRM8SV8RC</code><br>
        (Log in with your Clover merchant account if prompted)</li>
    <li>Press <b>F12</b> to open DevTools → click the <b>Network</b> tab</li>
    <li>Click the <b>XHR</b> or <b>Fetch</b> filter button (so only API calls show)</li>
    <li>Press <b>F5</b> to reload the page</li>
    <li>Click any request that starts with <code>api.clover.com</code> in the Name column</li>
    <li>In the right panel → <b>Headers</b> tab → scroll to <b>Request Headers</b></li>
    <li>Find <code>Authorization: Bearer xxxxxxxx</code> — copy everything after "Bearer " (it's a long string)</li>
    <li>Also note the App ID used in the URL (the part after <code>/apps/</code>)</li>
    <li>Paste both below and click <b>Test Token</b></li>
  </ol>
</div>

<?php if ($importResult): ?>
<div class="box ok">
  <b>✅ Import complete!</b><br>
  Updated: <b><?= $importResult['updated'] ?></b> &nbsp;|&nbsp;
  Not in loyalty DB: <b><?= $importResult['notFound'] ?></b> &nbsp;|&nbsp;
  Total members: <b><?= $importResult['total'] ?></b>
</div>
<table>
  <thead><tr><th>Customer</th><th>Points</th><th>Result</th></tr></thead>
  <tbody><?= $importResult['rows'] ?: '<tr><td colspan="3" style="color:#aaa">Nothing imported</td></tr>' ?></tbody>
</table>
<?php endif; ?>

<?php if ($testResult): ?>
<h3>Token Test Results</h3>
<div class="box <?= (array_sum(array_column($testResult,'code'))>=200 && min(array_column($testResult,'code'))<=200) ? 'ok' : 'warn' ?>">
  Endpoints that returned <b>200</b>: <?= count(array_filter($testResult, fn($r)=>$r['code']===200)) ?> / <?= count($testResult) ?>
  <?php $any200 = array_filter($testResult, fn($r)=>$r['code']===200 && !empty($r['data']['elements'])); ?>
  <?php if ($any200): ?> — <b style="color:green">✅ Found data! You can now do a full import.</b><?php endif; ?>
</div>
<table>
  <thead><tr><th>Path</th><th>Status</th><th>Response (preview)</th></tr></thead>
  <tbody>
  <?php foreach ($testResult as $r): ?>
  <tr <?= $r['code']===200 ? "style='background:#f1f8e9'" : ($r['code']===404 ? "style='color:#bbb'" : '') ?>>
    <td><code style="font-size:11px"><?= htmlspecialchars($r['path']) ?></code></td>
    <td><?= badge2($r['code']) ?></td>
    <td><pre><?= htmlspecialchars(mb_substr(json_encode($r['data'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),0,500)) ?></pre></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- ── Form ────────────────────────────────────────────────────────────────── -->
<form method="POST" style="margin-top:24px">
  <label>Session Bearer Token (from DevTools)</label>
  <textarea name="session_token" placeholder="Paste the token here — everything after 'Bearer ' in the Authorization header"><?= htmlspecialchars($sessionToken) ?></textarea>

  <label>App UUID (from the URL in DevTools — usually looks like <?= htmlspecialchars($appId) ?>)</label>
  <input type="text" name="app_id" value="<?= htmlspecialchars($appId) ?>">

  <label>Action</label>
  <select name="dry_run">
    <option value="1" <?= $dryRun ? 'selected' : '' ?>>Test only — probe endpoints, do NOT write to database</option>
    <option value="0" <?= !$dryRun ? 'selected' : '' ?>>Full import — pull all member points and write to database</option>
  </select>

  <button type="submit" class="btn-test">▶ Test Token</button>
</form>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_session_import.php from server after use. The session token gives full Clover access — keep it private.</p>
</body></html>
