<?php
// Clover Rewards (Perka CE API) — full member import
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
set_time_limit(300);
header('Content-Type: text/html; charset=utf-8');

$db    = get_db();
$nowMs = (int)(microtime(true) * 1000);

// ── Hardcoded credentials ─────────────────────────────────────────────────────
$MERCHANT_UUID     = '5037be2d-0bd7-4f41-8707-68f9c2014d37';
$LOCATION_UUID     = 'c11c519a-407a-4ae0-9815-ce97e8691b26';
$REFRESH_TOKEN     = 'UgaJl8vu9N3S7_Ef_5-12nGXXhMTwLrEalyLXt2ozZK4pDRtn6IkC3sOsjyP1QWc';
$REFRESH_TOK_UUID  = '505e75c1-5961-4ea6-a4c9-d86b57262c77';
$PERKA_USER_UUID   = '20738e96-8c7e-4b3c-8e1c-643f49860708';
$CE_BASE           = 'https://api.clover.com/customer-engagement/1';

// ── HTTP helper ───────────────────────────────────────────────────────────────
function ce(string $method, string $url, string $token, array $body = []): array {
    $ch = curl_init($url);
    $hdrs = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) $hdrs[] = 'Authorization: Bearer ' . $token;
    $opts = [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20,
             CURLOPT_CUSTOMREQUEST=>$method, CURLOPT_HTTPHEADER=>$hdrs];
    if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    curl_setopt_array($ch, $opts);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code'=>$code, 'data'=>($raw ? json_decode($raw,true) : null), 'raw'=>$raw];
}

// ── Step 1: Try multiple refresh methods ──────────────────────────────────────
$refreshBase = $CE_BASE . '/user/refresh/token?merchantUuid=' . $MERCHANT_UUID;
$refreshAttempts = [
    // Most likely: POST with refresh token as Bearer auth
    ['POST', $refreshBase, $REFRESH_TOKEN, []],
    ['POST', $refreshBase, $REFRESH_TOKEN, ['refreshTokenUuid' => $REFRESH_TOK_UUID]],
    ['POST', $refreshBase, $REFRESH_TOKEN, ['refreshToken'     => $REFRESH_TOKEN]],
    ['POST', $refreshBase, $REFRESH_TOKEN, ['perkaUserUuid'    => $PERKA_USER_UUID, 'refreshTokenUuid' => $REFRESH_TOK_UUID]],
    // Try GET with query param
    ['GET',  $refreshBase . '&refreshToken=' . urlencode($REFRESH_TOKEN), $REFRESH_TOKEN, []],
    // Try without any auth (token in body only)
    ['POST', $refreshBase, '', ['refreshToken' => $REFRESH_TOKEN, 'merchantUuid' => $MERCHANT_UUID]],
];

$SESSION_TOKEN = null;
$refreshLog    = [];
foreach ($refreshAttempts as [$method, $url, $tok, $body]) {
    $r = ce($method, $url, $tok, $body);
    $label = $method . ' body=' . json_encode($body);
    $refreshLog[] = ['label'=>$label,'code'=>$r['code'],'data'=>$r['data']];
    if ($r['code'] === 200 && !empty($r['data']['data']['authorizationToken'])) {
        foreach ($r['data']['data']['authorizationToken'] as $t) {
            if (($t['type']??'') === 'SESSION') { $SESSION_TOKEN = $t['token']; break; }
        }
        if ($SESSION_TOKEN) break;
    }
}

// ── Step 2: Get launch token ──────────────────────────────────────────────────
$LAUNCH_TOKEN = null;
$launchCode   = null;
if ($SESSION_TOKEN) {
    $launchUrl = $CE_BASE . '/clover/product/launch/' . $LOCATION_UUID . '?productType=REWARDS';
    $r2 = ce('GET', $launchUrl, $SESSION_TOKEN);
    $launchCode = $r2['code'];
    if ($r2['code'] === 200 && $r2['data']) {
        $d = $r2['data'];
        $LAUNCH_TOKEN = $d['token'] ?? $d['accessToken'] ?? $d['access_token'] ??
                        ($d['data']['authorizationToken'][0]['token'] ?? null) ?? null;
    }
    // If no separate launch token, use session token
    if (!$LAUNCH_TOKEN) $LAUNCH_TOKEN = $SESSION_TOKEN;
}

// ── Step 3: Probe member endpoints ────────────────────────────────────────────
// Build token candidates (deduplicated, non-null)
$tokCandidates = array_unique(array_filter([$LAUNCH_TOKEN, $SESSION_TOKEN, $REFRESH_TOKEN]));

// All plausible paths — primary candidates first based on the launch URL pattern
$memberPaths = [
    // PRIMARY: same resource as launch but without 'launch' path segment
    "/clover/product/{$LOCATION_UUID}?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/members?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/customers?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/users?page=0&pageSize=999",
    // Merchant-based
    "/clover/merchants/{$MERCHANT_UUID}/members?page=0&pageSize=999",
    "/clover/merchants/{$MERCHANT_UUID}/memberships?page=0&pageSize=999",
    "/clover/merchants/{$MERCHANT_UUID}?page=0&pageSize=999",
    // User / social namespace
    "/user/memberships/{$LOCATION_UUID}?page=0&pageSize=999",
    "/user/memberships?merchantLocationUuid={$LOCATION_UUID}&page=0&pageSize=999",
    "/user/memberships?merchantUuid={$MERCHANT_UUID}&page=0&pageSize=999",
    "/social/groups/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/social/memberships/{$LOCATION_UUID}?page=0&pageSize=999",
    // Overview / stats for structure hints
    "/clover/product/{$LOCATION_UUID}/overview",
    "/clover/product/{$LOCATION_UUID}/stats",
];

$allProbes = [];
foreach ($memberPaths as $path) {
    foreach ($tokCandidates as $tok) {
        $allProbes[] = ['url'=>$CE_BASE.$path, 'path'=>$path,
                        'tokPreview'=>substr($tok,0,12).'...', 'token'=>$tok];
    }
}

// Parallel curl
$mh = curl_multi_init();
$handles = [];
foreach ($allProbes as $k => $p) {
    $ch = curl_init($p['url']);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$p['token'],'Accept: application/json']]);
    $handles[$k] = $ch;
    curl_multi_add_handle($mh, $ch);
}
do { $s = curl_multi_exec($mh, $act); if ($act) curl_multi_select($mh); }
while ($act && $s == CURLM_OK);
$probeResults = [];
foreach ($handles as $k => $ch) {
    $raw = curl_multi_getcontent($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $probeResults[$k] = ['code'=>$code, 'data'=>($raw?json_decode($raw,true):null), 'raw'=>$raw];
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// Find working endpoint
function extract_list(array $data): array {
    foreach (['memberships','members','elements','users','customers','data','items','results'] as $key) {
        if (isset($data[$key]) && is_array($data[$key]) && count($data[$key]) > 0) return $data[$key];
    }
    if (isset($data[0]) && is_array($data[0])) return $data;
    return [];
}

$bestPath = $bestToken = $sample = null;
foreach ($allProbes as $k => $p) {
    $r = $probeResults[$k];
    if ($r['code'] !== 200 || !$r['data']) continue;
    $els = extract_list($r['data']);
    if (count($els) > 0) {
        $bestPath  = $p['path'];
        $bestToken = $p['token'];
        $sample    = $els[0];
        break;
    }
}

// ── Step 4: Import ─────────────────────────────────────────────────────────────
$importResult = null;
if (isset($_POST['do_import']) && $bestPath && $bestToken) {
    $updated = $notFound = $noPhone = $total = 0;
    $rows = '';
    $page = 0;
    $pageSize = 500;

    do {
        $url = $CE_BASE . preg_replace('/\?.*/', '', $bestPath)
             . '?page=' . $page . '&pageSize=' . $pageSize;
        $r = ce('GET', $url, $bestToken);
        if ($r['code'] !== 200) break;
        $batch = extract_list($r['data'] ?? []);
        if (!$batch) break;

        foreach ($batch as $m) {
            $total++;
            $rawPhone = preg_replace('/\D/', '',
                $m['phone'] ?? $m['phoneNumber'] ?? $m['mobile'] ??
                ($m['user']['phone'] ?? $m['user']['phoneNumber'] ?? '') ??
                ($m['customer']['phone'] ?? ''));

            $pts = (int)(
                $m['points'] ?? $m['balance'] ?? $m['pointBalance'] ??
                $m['loyaltyPoints'] ?? $m['rewardPoints'] ??
                ($m['user']['points'] ?? 0)
            );

            $firstName = $m['firstName'] ?? $m['user']['firstName'] ?? '';
            $lastName  = $m['lastName']  ?? $m['user']['lastName']  ?? '';
            $name = trim("$firstName $lastName") ?: ($m['name'] ?? $rawPhone);

            if (!$rawPhone) { $noPhone++; continue; }

            $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone=? OR phone=? LIMIT 1');
            $stmt->execute([$rawPhone, ltrim($rawPhone,'1')]);
            $cust = $stmt->fetch();

            if (!$cust) {
                $notFound++;
                $rows .= "<tr><td>".htmlspecialchars($rawPhone)."</td><td>$pts</td><td style='color:orange'>Not in loyalty DB</td></tr>";
                continue;
            }

            $tier = tier_from_points($pts);
            $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')->execute([$pts,$tier,$nowMs,$cust['id']]);
            if ($pts > 0) {
                $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
                   ->execute([uuid4(),$cust['id'],$pts,'Clover Rewards import '.date('Y-m-d'),$nowMs]);
            }
            $updated++;
            $color = $pts > 0 ? 'green' : '#888';
            $rows .= "<tr><td>".htmlspecialchars($name)."</td><td>$pts</td><td style='color:$color'>✅ $pts pts ($tier)</td></tr>";
        }
        $page++;
    } while (count($batch) === $pageSize && $page < 20);

    $importResult = compact('updated','notFound','noPhone','total','rows');
}

function badge(int $c): string {
    if ($c===200) return "<b style='color:green'>200 ✅</b>";
    if ($c===401) return "<b style='color:#c62828'>401</b>";
    if ($c===403) return "<b style='color:#c62828'>403</b>";
    if ($c===404) return "<span style='color:#bbb'>404</span>";
    if ($c===405) return "<b style='color:orange'>405</b>";
    return "<b>$c</b>";
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Rewards Import</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:1020px;background:#fafafa}
h2,h3{margin-bottom:4px}h3{color:#1565c0;margin-top:20px}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px}
th,td{padding:5px 9px;border:1px solid #ddd;vertical-align:top;text-align:left}th{background:#f0f0f0}
pre{margin:0;font-size:11px;max-height:160px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
.box{padding:12px 17px;border-radius:7px;margin-bottom:10px;font-size:14px}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
.warn{background:#fff8e1;border:1px solid #ffe082}
.err{background:#ffebee;border:1px solid #ef9a9a}
.info{background:#e3f2fd;border:1px solid #90caf9}
button{padding:11px 28px;background:#2e7d32;color:#fff;border:none;border-radius:6px;font-size:15px;cursor:pointer;font-weight:bold;margin-top:10px}
button:hover{background:#1b5e20}
code{background:#eee;padding:1px 5px;border-radius:3px;font-size:12px}
details summary{cursor:pointer;color:#1565c0}
</style></head><body>
<h2>Clover Rewards Member Import</h2>
<p style="color:#666">Merchant: <code><?= $MERCHANT_UUID ?></code> &nbsp;|&nbsp; Location: <code><?= $LOCATION_UUID ?></code></p>

<h3>1. Token Refresh</h3>
<?php if ($SESSION_TOKEN): ?>
<div class="box ok">✅ Session token obtained successfully.</div>
<?php else: ?>
<div class="box err">❌ All refresh attempts failed — see log below.</div>
<?php endif; ?>
<details><summary>Refresh attempt log (<?= count($refreshLog) ?> tries)</summary>
<table style="margin-top:6px"><thead><tr><th>Attempt</th><th>HTTP</th><th>Response</th></tr></thead><tbody>
<?php foreach ($refreshLog as $rl): ?>
<tr <?= $rl['code']===200?"style='background:#f1f8e9'":'' ?>><td><code style="font-size:11px"><?= htmlspecialchars($rl['label']) ?></code></td><td><?= badge($rl['code']) ?></td><td><pre><?= htmlspecialchars(mb_substr(json_encode($rl['data'],JSON_PRETTY_PRINT),0,300)) ?></pre></td></tr>
<?php endforeach; ?>
</tbody></table>
</details>

<h3>2. Launch Token</h3>
<?php if ($LAUNCH_TOKEN && $LAUNCH_TOKEN !== $SESSION_TOKEN): ?>
<div class="box ok">✅ Launch token obtained (HTTP <?= $launchCode ?>).</div>
<?php elseif ($SESSION_TOKEN): ?>
<div class="box warn">⚠️ Using session token as launch token (HTTP <?= $launchCode ?> from launch endpoint).</div>
<?php else: ?>
<div class="box err">❌ Skipped — no session token.</div>
<?php endif; ?>

<h3>3. Member List Probe (<?= count($allProbes) ?> endpoint × token combos)</h3>
<?php if ($bestPath): ?>
<div class="box ok">
  ✅ <b>Found!</b> Path: <code><?= htmlspecialchars($bestPath) ?></code><br>
  Sample: <pre style="margin-top:6px"><?= htmlspecialchars(json_encode($sample,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
</div>
<?php else: ?>
<div class="box err">❌ No endpoint returned list data yet.</div>
<?php endif; ?>

<details><summary>Show all probe results</summary>
<table style="margin-top:6px"><thead><tr><th>Token</th><th>Path</th><th>HTTP</th><th>Preview</th></tr></thead><tbody>
<?php foreach ($allProbes as $k => $p):
    $r = $probeResults[$k] ?? ['code'=>0,'data'=>null,'raw'=>''];
    $isBest = $bestPath && $p['path']===$bestPath && $p['token']===$bestToken;
    $preview = $r['data'] ? json_encode($r['data'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : $r['raw'];
?>
<tr <?= $isBest?"style='background:#e8f5e9;font-weight:bold'":($r['code']===404||$r['code']===405?"style='color:#bbb'":"") ?>>
  <td><code style="font-size:10px"><?= htmlspecialchars($p['tokPreview']) ?></code></td>
  <td><code style="font-size:10px"><?= htmlspecialchars($p['path']) ?></code></td>
  <td><?= badge($r['code']) ?></td>
  <td><pre><?= htmlspecialchars(mb_substr($preview??'',0,250)) ?></pre></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</details>

<?php if ($importResult): ?>
<h3>4. Import Result</h3>
<div class="box ok">✅ Done! Updated: <b><?= $importResult['updated'] ?></b> | Not found: <b><?= $importResult['notFound'] ?></b> | No phone: <b><?= $importResult['noPhone'] ?></b> | Total: <b><?= $importResult['total'] ?></b></div>
<table><thead><tr><th>Customer</th><th>Points</th><th>Result</th></tr></thead>
<tbody><?= $importResult['rows']?:'<tr><td colspan="3" style="color:#aaa">Nothing</td></tr>' ?></tbody>
</table>
<?php elseif ($bestPath): ?>
<h3>4. Import</h3>
<div class="box info">Found member data. Click to import all into loyalty database (Clover points will overwrite existing points).</div>
<form method="POST"><input type="hidden" name="do_import" value="1">
<button type="submit">⬆ Import All Members Now</button></form>
<?php endif; ?>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_rewards_import.php from server after use.</p>
</body></html>
