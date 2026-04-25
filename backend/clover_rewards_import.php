<?php
// Clover Rewards member import — uses /clover/inventory/{locationUuid}
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
set_time_limit(300);
header('Content-Type: text/html; charset=utf-8');

$db    = get_db();
$nowMs = (int)(microtime(true) * 1000);

$MERCHANT_UUID = '5037be2d-0bd7-4f41-8707-68f9c2014d37';
$LOCATION_UUID = 'c11c519a-407a-4ae0-9815-ce97e8691b26';
$CE_BASE       = 'https://api.clover.com/customer-engagement/1';
// Known endpoints (inventory = menu items, not members)
$INVENTORY_PATH  = "/clover/inventory/{$LOCATION_UUID}";
$PROGRAM_UUID    = '0020168f-ec91-4f72-89e9-6fad1da43319';

// All candidate member endpoints to probe
$MEMBER_PATHS = [
    "/clover/membership/{$LOCATION_UUID}?page=0&pageSize=999",
    "/clover/memberships/{$LOCATION_UUID}?page=0&pageSize=999",
    "/clover/members/{$LOCATION_UUID}?page=0&pageSize=999",
    "/clover/membership/{$MERCHANT_UUID}?page=0&pageSize=999",
    "/clover/memberships/{$MERCHANT_UUID}?page=0&pageSize=999",
    "/clover/members/{$MERCHANT_UUID}?page=0&pageSize=999",
    "/clover/programs/{$PROGRAM_UUID}/members?page=0&pageSize=999",
    "/clover/programs/{$PROGRAM_UUID}/memberships?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/membership?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/social/memberships?merchantUuid={$MERCHANT_UUID}&page=0&pageSize=999",
    "/social/members/{$MERCHANT_UUID}?page=0&pageSize=999",
    "/user/membershipState/{$LOCATION_UUID}?page=0&pageSize=999",
    // Stats / overview (to understand data structure)
    "/clover/product/{$LOCATION_UUID}/overview",
    "/clover/product/{$LOCATION_UUID}/stats",
    "/clover/product/{$LOCATION_UUID}/stats?includePerks=false",
    "/clover/merchants/{$MERCHANT_UUID}/overview",
    // History / visits
    "/clover/history/{$LOCATION_UUID}?page=0&pageSize=20",
    "/clover/history/{$MERCHANT_UUID}?page=0&pageSize=20",
    "/clover/visits/{$LOCATION_UUID}?page=0&pageSize=20",
    "/clover/product/{$LOCATION_UUID}/history?page=0&pageSize=20",
];

// Token pasted from DevTools (Authorization: Bearer xxxxx on the inventory request)
$LAUNCH_TOKEN = trim($_POST['launch_token'] ?? '');

function ce_get(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => ($raw ? json_decode($raw, true) : null), 'raw' => $raw];
}

// ── Probe / import ────────────────────────────────────────────────────────────
$probeResult = null;
$importResult = null;

if ($LAUNCH_TOKEN) {
    // Probe all member candidate endpoints in parallel
    $mh = curl_multi_init();
    $handles = [];
    foreach ($MEMBER_PATHS as $k => $path) {
        $ch = curl_init($CE_BASE . $path);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$LAUNCH_TOKEN,'Accept: application/json']]);
        $handles[$k] = $ch;
        curl_multi_add_handle($mh, $ch);
    }
    do { $s = curl_multi_exec($mh, $act); if ($act) curl_multi_select($mh); }
    while ($act && $s == CURLM_OK);
    $memberProbes = [];
    foreach ($handles as $k => $ch) {
        $raw = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $memberProbes[$k] = ['code'=>$code,'data'=>($raw?json_decode($raw,true):null),'raw'=>$raw];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    // Also probe original inventory endpoint (already known to work)
    $probeUrl = $CE_BASE . $INVENTORY_PATH . '?page=0&pageSize=5';
    $probeResult = ce_get($probeUrl, $LAUNCH_TOKEN);

    if (isset($_POST['do_import']) && $probeResult['code'] === 200) {
        $updated = $notFound = $noPhone = $total = 0;
        $rows = '';
        $page = 0;
        $pageSize = 500;

        do {
            $url = $CE_BASE . $INVENTORY_PATH . '?page=' . $page . '&pageSize=' . $pageSize;
            $r   = ce_get($url, $LAUNCH_TOKEN);
            if ($r['code'] !== 200) {
                $rows .= "<tr><td colspan='3' style='color:red'>❌ HTTP {$r['code']} on page {$page} — stopped.</td></tr>";
                break;
            }
            $d = $r['data'];

            // Extract list — try every known field name
            $batch = null;
            foreach (['memberships','members','elements','users','customers','data','items','results','inventory'] as $key) {
                if (isset($d[$key]) && is_array($d[$key])) { $batch = $d[$key]; break; }
            }
            if ($batch === null && isset($d[0])) $batch = $d;
            if (!$batch) break;

            foreach ($batch as $m) {
                $total++;
                // Extract phone — try many field paths
                $rawPhone = preg_replace('/\D/', '',
                    $m['phone'] ?? $m['phoneNumber'] ?? $m['mobile'] ??
                    ($m['user']['phone'] ?? $m['user']['phoneNumber'] ??
                    ($m['customer']['phone'] ?? $m['consumer']['phone'] ??
                    ($m['identity']['phone'] ?? ''))));

                // Extract points
                $pts = (int)(
                    $m['points'] ?? $m['balance'] ?? $m['pointBalance'] ??
                    $m['loyaltyPoints'] ?? $m['rewardPoints'] ??
                    $m['currentPoints'] ?? $m['totalPoints'] ??
                    ($m['user']['points'] ?? $m['customer']['points'] ??
                    ($m['loyalty']['points'] ?? 0))
                );

                $firstName = $m['firstName'] ?? $m['user']['firstName'] ?? $m['customer']['firstName'] ?? '';
                $lastName  = $m['lastName']  ?? $m['user']['lastName']  ?? $m['customer']['lastName']  ?? '';
                $name = trim("$firstName $lastName") ?: ($m['name'] ?? $m['user']['name'] ?? $rawPhone ?: '—');

                if (!$rawPhone) {
                    $noPhone++;
                    $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td>$pts</td><td style='color:#aaa'>No phone number</td></tr>";
                    continue;
                }

                $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone=? OR phone=? LIMIT 1');
                $stmt->execute([$rawPhone, ltrim($rawPhone, '1')]);
                $cust = $stmt->fetch();

                if (!$cust) {
                    $notFound++;
                    $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td>$pts</td><td style='color:orange'>Phone $rawPhone not in loyalty DB</td></tr>";
                    continue;
                }

                $tier = tier_from_points($pts);
                $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')
                   ->execute([$pts, $tier, $nowMs, $cust['id']]);
                if ($pts > 0) {
                    $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
                       ->execute([uuid4(), $cust['id'], $pts, 'Clover Rewards import ' . date('Y-m-d'), $nowMs]);
                }
                $updated++;
                $color = $pts > 0 ? '#2e7d32' : '#888';
                $rows .= "<tr><td>" . htmlspecialchars($name) . "</td><td>$pts</td><td style='color:$color'>✅ Set to <b>$pts</b> pts ($tier)</td></tr>";
            }

            $page++;
        } while (count($batch) === $pageSize && $page < 20);

        $importResult = compact('updated', 'notFound', 'noPhone', 'total', 'rows');
    }
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Rewards Import</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:980px;background:#fafafa}
h2,h3{margin-bottom:4px}h3{color:#1565c0;margin-top:22px}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px}
th,td{padding:6px 10px;border:1px solid #ddd;vertical-align:top;text-align:left}th{background:#f0f0f0}
pre{margin:0;font-size:11px;max-height:300px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;background:#fff;border:1px solid #eee;padding:10px;border-radius:4px;margin-top:6px}
.box{padding:13px 18px;border-radius:8px;margin-bottom:10px;font-size:14px}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
.warn{background:#fff8e1;border:1px solid #ffe082}
.err{background:#ffebee;border:1px solid #ef9a9a}
.info{background:#e3f2fd;border:1px solid #90caf9}
button{padding:11px 28px;border:none;border-radius:6px;font-size:15px;cursor:pointer;font-weight:bold;margin-top:10px}
.btn-g{background:#2e7d32;color:#fff}.btn-g:hover{background:#1b5e20}
.btn-b{background:#1565c0;color:#fff}.btn-b:hover{background:#0d47a1}
label{display:block;font-weight:bold;font-size:13px;margin-top:14px}
textarea{width:100%;height:55px;font-family:monospace;font-size:12px;padding:8px;box-sizing:border-box;border:1px solid #ccc;border-radius:4px}
code{background:#eee;padding:1px 5px;border-radius:3px;font-size:12px}
ol li{margin-bottom:8px;line-height:1.6}
</style></head><body>
<h2>Clover Rewards Member Import</h2>
<p style="color:#666">
  Merchant: <code><?= $MERCHANT_UUID ?></code> &nbsp;|&nbsp;
  Location: <code><?= $LOCATION_UUID ?></code><br>
  Endpoint: <code><?= $CE_BASE . $INVENTORY_PATH ?>?page=0&amp;pageSize=999</code>
</p>

<!-- ── How to get the token ───────────────────────────────────────────────── -->
<div class="box info">
  <b>📋 How to get a fresh launch token (takes ~1 minute):</b>
  <ol>
    <li>Go to <b>www.clover.com/rewards/overview?client_id=1EVSVRM8SV8RC</b> (logged in as merchant)</li>
    <li>Press <b>F12</b> → Network tab → click <b>XHR</b> or <b>All</b> filter → press <b>F5</b></li>
    <li>In the list, find the request named <code>c11c519a-407a-4ae0-9815-ce97e8691b26?page=0&amp;pageSize=999</code></li>
    <li>Click it → right panel → <b>Headers</b> tab → scroll to <b>Request Headers</b></li>
    <li>Copy the value after <code>Authorization: Bearer </code> (a long string)</li>
    <li>Paste it in the box below</li>
  </ol>
  <b>Token expires ~1 hour</b> after the dashboard was loaded, so do this quickly!
</div>

<!-- ── Member endpoint probe results ─────────────────────────────────────── -->
<?php if (isset($memberProbes)): ?>
<h3>Member Endpoint Probe (<?= count($MEMBER_PATHS) ?> paths)</h3>
<?php
$found200 = array_filter($memberProbes, fn($r)=>$r['code']===200);
if ($found200): ?>
<div class="box ok">✅ Some endpoints returned 200! Check the green rows.</div>
<?php else: ?>
<div class="box warn">⚠️ No 200 yet — see raw responses for clues.</div>
<?php endif; ?>
<table><thead><tr><th>Path</th><th>HTTP</th><th>Response (first 300 chars)</th></tr></thead><tbody>
<?php foreach ($MEMBER_PATHS as $k => $path):
    $r = $memberProbes[$k] ?? ['code'=>0,'data'=>null,'raw'=>''];
    $preview = $r['data'] ? json_encode($r['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : $r['raw'];
    $color = $r['code']===200?'background:#e8f5e9':($r['code']===404?'color:#bbb':'');
?>
<tr style="<?= $color ?>">
  <td><code style="font-size:11px"><?= htmlspecialchars($path) ?></code></td>
  <td><b><?= $r['code'] ?></b></td>
  <td><pre><?= htmlspecialchars(mb_substr($preview??'',0,300)) ?></pre></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<!-- ── Inventory probe (known working) ──────────────────────────────────── -->
<?php if ($probeResult): ?>
<h3>Token Check (inventory endpoint — menu items, not members)</h3>
<?php if ($probeResult['code'] === 200): ?>
<div class="box ok">✅ Token is valid (HTTP 200 on inventory endpoint).</div>
<?php else: ?>
<div class="box err">❌ HTTP <?= $probeResult['code'] ?> — token expired. Get a fresh one from DevTools.</div>
<pre><?= htmlspecialchars(json_encode($probeResult['data'], JSON_PRETTY_PRINT)) ?></pre>
<?php endif; ?>
<?php endif; ?>

<!-- ── Import result ──────────────────────────────────────────────────────── -->
<?php if ($importResult): ?>
<h3>Import Result</h3>
<div class="box ok">
  ✅ <b>Done!</b> &nbsp;
  Updated: <b><?= $importResult['updated'] ?></b> &nbsp;|&nbsp;
  Not in loyalty DB: <b><?= $importResult['notFound'] ?></b> &nbsp;|&nbsp;
  No phone: <b><?= $importResult['noPhone'] ?></b> &nbsp;|&nbsp;
  Total members: <b><?= $importResult['total'] ?></b>
</div>
<table><thead><tr><th>Customer</th><th>Clover Points</th><th>Result</th></tr></thead>
<tbody><?= $importResult['rows'] ?: '<tr><td colspan="3" style="color:#aaa">Nothing imported</td></tr>' ?></tbody>
</table>
<?php endif; ?>

<!-- ── Form ────────────────────────────────────────────────────────────────── -->
<form method="POST" style="margin-top:20px">
  <label>Launch Token (paste from DevTools — Authorization: Bearer <i>xxxxx</i>)</label>
  <textarea name="launch_token" required placeholder="nQVn6OD7VQNdyuV1-Of18Mxp7tlAgg..."><?= htmlspecialchars($LAUNCH_TOKEN) ?></textarea>

  <?php if ($probeResult && $probeResult['code'] === 200 && !$importResult): ?>
  <!-- Token works — show import button -->
  <div class="box warn" style="margin-top:12px">
    ⚠️ This will <b>overwrite</b> existing loyalty points with the Clover Rewards values.
  </div>
  <input type="hidden" name="do_import" value="1">
  <button type="submit" class="btn-g">⬆ Import All Members Now</button>
  &nbsp;&nbsp;
  <button type="submit" formnovalidate class="btn-b" onclick="this.form.do_import.value=''">🔍 Re-probe Only</button>
  <?php else: ?>
  <button type="submit" class="btn-b">🔍 Test Token &amp; Preview</button>
  <?php endif; ?>
</form>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_rewards_import.php from server after use.</p>
</body></html>
