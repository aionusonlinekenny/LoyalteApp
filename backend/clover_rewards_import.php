<?php
// Clover Rewards (Perka / customer-engagement API) full member import
// Complete auth flow: refresh → session → launch token → members
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
set_time_limit(300);
header('Content-Type: text/html; charset=utf-8');

$db    = get_db();
$nowMs = (int)(microtime(true) * 1000);

// ── Hardcoded credentials (from browser DevTools) ─────────────────────────────
$MERCHANT_UUID = '5037be2d-0bd7-4f41-8707-68f9c2014d37';
$LOCATION_UUID = 'c11c519a-407a-4ae0-9815-ce97e8691b26';
$REFRESH_TOKEN = 'UgaJl8vu9N3S7_Ef_5-12nGXXhMTwLrEalyLXt2ozZK4pDRtn6IkC3sOsjyP1QWc';
$CE_BASE       = 'https://api.clover.com/customer-engagement/1';

// ── HTTP helpers ──────────────────────────────────────────────────────────────
function ce_req(string $method, string $url, string $token, array $body = []): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json',
        ],
    ];
    if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => ($resp ? json_decode($resp, true) : null), 'raw' => $resp];
}

// ── Step 1: Refresh → get fresh SESSION token ─────────────────────────────────
// Auth: use REFRESH_TOKEN as Bearer
$refreshUrl = $CE_BASE . '/user/refresh/token?merchantUuid=' . $MERCHANT_UUID;
$r1 = ce_req('GET', $refreshUrl, $REFRESH_TOKEN);

$SESSION_TOKEN = null;
if ($r1['code'] === 200 && isset($r1['data']['data']['authorizationToken'])) {
    foreach ($r1['data']['data']['authorizationToken'] as $t) {
        if (($t['type'] ?? '') === 'SESSION') {
            $SESSION_TOKEN = $t['token'];
            break;
        }
    }
}

// ── Step 2: Launch → get LAUNCH token (used for member queries) ───────────────
$LAUNCH_TOKEN = null;
$launchData   = null;
if ($SESSION_TOKEN) {
    $launchUrl = $CE_BASE . '/clover/product/launch/' . $LOCATION_UUID . '?productType=REWARDS';
    $r2 = ce_req('GET', $launchUrl, $SESSION_TOKEN);
    $launchData = $r2;

    // The launch response contains a token in various possible locations
    if ($r2['code'] === 200 && $r2['data']) {
        $d = $r2['data'];
        // Try common token field locations in launch response
        $LAUNCH_TOKEN = $d['token'] ?? $d['accessToken'] ?? $d['access_token'] ??
                        $d['data']['token'] ?? $d['data']['accessToken'] ?? null;
        // If not found, the session token itself may be the launch token
        if (!$LAUNCH_TOKEN) $LAUNCH_TOKEN = $SESSION_TOKEN;
    }
}

// ── Step 3: Probe member list endpoints with LAUNCH token ─────────────────────
// The DevTools showed: c11c519a...?page=0&pageSize=999
// Try all plausible paths ending in the location UUID
$memberPaths = [
    "/clover/product/{$LOCATION_UUID}?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/members?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/clover/product/{$LOCATION_UUID}/users?page=0&pageSize=999",
    "/clover/merchants/{$LOCATION_UUID}?page=0&pageSize=999",
    "/clover/merchants/{$LOCATION_UUID}/members?page=0&pageSize=999",
    "/clover/merchants/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/user/memberships/{$LOCATION_UUID}?page=0&pageSize=999",
    "/user/memberships?merchantLocationUuid={$LOCATION_UUID}&page=0&pageSize=999",
    "/social/memberships/{$LOCATION_UUID}?page=0&pageSize=999",
    "/social/groups/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/clover/merchants/{$MERCHANT_UUID}?page=0&pageSize=999",
    "/clover/merchants/{$MERCHANT_UUID}/members?page=0&pageSize=999",
    "/user/memberships?merchantUuid={$MERCHANT_UUID}&page=0&pageSize=999",
    "/social/groups/{$MERCHANT_UUID}/memberships?page=0&pageSize=999",
];

// Run probes in parallel with both launch token and session token
$probeTokens = array_filter([$LAUNCH_TOKEN, $SESSION_TOKEN], fn($t)=>!is_null($t));
$allProbes   = [];
foreach ($memberPaths as $path) {
    foreach (array_unique($probeTokens) as $tok) {
        $allProbes[] = ['url' => $CE_BASE . $path, 'path' => $path,
                        'label' => ($tok === $LAUNCH_TOKEN ? 'launch' : 'session'), 'token' => $tok];
    }
}

$mh = curl_multi_init();
$handles = [];
foreach ($allProbes as $k => $p) {
    $ch = curl_init($p['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $p['token'],
            'Accept: application/json',
        ],
    ]);
    $handles[$k] = $ch;
    curl_multi_add_handle($mh, $ch);
}
do { $s = curl_multi_exec($mh, $act); if ($act) curl_multi_select($mh); }
while ($act && $s == CURLM_OK);
$probeResults = [];
foreach ($handles as $k => $ch) {
    $body = curl_multi_getcontent($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $probeResults[$k] = ['code' => $code, 'data' => ($body ? json_decode($body, true) : null), 'raw' => $body];
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// ── Find the first endpoint that returns member-like data ─────────────────────
$bestPath  = null;
$bestToken = null;
$sample    = null;

function looks_like_members(array $data): array {
    // Try various field names for member lists
    foreach (['memberships','members','elements','users','data','items'] as $key) {
        if (isset($data[$key]) && is_array($data[$key]) && count($data[$key]) > 0) {
            return $data[$key];
        }
    }
    // Root array of objects
    if (isset($data[0]) && is_array($data[0])) return $data;
    return [];
}

foreach ($allProbes as $k => $p) {
    $r = $probeResults[$k];
    if ($r['code'] !== 200 || !$r['data']) continue;
    $els = looks_like_members($r['data']);
    if (count($els) > 0) {
        $bestPath  = $p['path'];
        $bestToken = $p['token'];
        $sample    = $els[0];
        break;
    }
}

// ── Step 4: Full import ───────────────────────────────────────────────────────
$importResult = null;
$doImport = isset($_POST['do_import']) && $bestPath && $bestToken;

if ($doImport) {
    $updated = $notFound = $noPhone = $total = 0;
    $rows = '';
    $page = 0;
    $pageSize = 500;

    do {
        $pagePath = preg_replace('/page=\d+/', "page={$page}", $bestPath);
        $pagePath = preg_replace('/pageSize=\d+/', "pageSize={$pageSize}", $pagePath);
        // Remove existing page/pageSize params and re-add
        $base = preg_replace('/[?&]page(Size)?=\d+/', '', $CE_BASE . $pagePath);
        $sep  = strpos($base, '?') !== false ? '&' : '?';
        $url  = $base . $sep . "page={$page}&pageSize={$pageSize}";
        $r    = ce_req('GET', $url, $bestToken);
        if ($r['code'] !== 200) break;
        $batch = looks_like_members($r['data'] ?? []);
        if (!$batch) break;

        foreach ($batch as $m) {
            $total++;
            $rawPhone = preg_replace('/\D/', '',
                $m['phone'] ?? $m['phoneNumber'] ?? $m['mobile'] ??
                $m['user']['phone'] ?? $m['user']['phoneNumber'] ??
                $m['customer']['phone'] ?? '');

            $pts = (int)(
                $m['points'] ?? $m['balance'] ?? $m['pointBalance'] ??
                $m['loyaltyPoints'] ?? $m['user']['points'] ??
                $m['customer']['points'] ?? 0
            );

            $firstName = $m['firstName'] ?? $m['user']['firstName'] ?? $m['customer']['firstName'] ?? '';
            $lastName  = $m['lastName']  ?? $m['user']['lastName']  ?? $m['customer']['lastName']  ?? '';
            $name      = trim("$firstName $lastName") ?: ($m['name'] ?? $m['user']['name'] ?? $rawPhone);

            if (!$rawPhone) {
                $noPhone++;
                $rows .= "<tr><td style='color:#aaa'>" . htmlspecialchars($name ?: '—') . "</td><td>$pts</td><td style='color:#aaa'>No phone</td></tr>";
                continue;
            }

            $stmt = $db->prepare('SELECT id, points FROM customers WHERE phone=? OR phone=? LIMIT 1');
            $stmt->execute([$rawPhone, ltrim($rawPhone, '1')]);
            $cust = $stmt->fetch();

            if (!$cust) {
                $notFound++;
                $rows .= "<tr><td>" . htmlspecialchars($rawPhone) . "</td><td>$pts</td><td style='color:orange'>Not in loyalty DB</td></tr>";
                continue;
            }

            $tier = tier_from_points($pts);
            $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')->execute([$pts, $tier, $nowMs, $cust['id']]);
            if ($pts > 0) {
                $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
                   ->execute([uuid4(), $cust['id'], $pts, 'Clover Rewards import ' . date('Y-m-d'), $nowMs]);
            }
            $updated++;
            $color = $pts > 0 ? 'green' : '#888';
            $rows .= "<tr><td>" . htmlspecialchars(trim($name)) . "</td><td>$pts</td><td style='color:$color'>✅ Set to <b>$pts</b> pts ($tier)</td></tr>";
        }

        $page++;
    } while (count($batch) === $pageSize && $page < 20);

    $importResult = compact('updated', 'notFound', 'noPhone', 'total', 'rows');
}

function badge(int $c): string {
    $map = [200=>'<span class="b200">200 ✅</span>', 401=>'<span class="b401">401</span>',
            403=>'<span class="b403">403</span>', 404=>'<span class="b404">404</span>',
            405=>'<span class="b405">405</span>'];
    return $map[$c] ?? "<span class='bx'>$c</span>";
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Rewards Import</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:1000px;background:#fafafa}
h2,h3{margin-bottom:4px}h3{color:#1565c0;margin-top:22px}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px}
th,td{padding:6px 10px;border:1px solid #ddd;vertical-align:top;text-align:left}
th{background:#f0f0f0}
pre{margin:0;font-size:11px;max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
.b200{background:#2e7d32;color:#fff;padding:2px 6px;border-radius:4px;font-weight:bold}
.b401,.b403,.b405{background:#e57373;color:#fff;padding:2px 6px;border-radius:4px}
.b404{background:#bdbdbd;color:#333;padding:2px 6px;border-radius:4px}
.bx{background:#90a4ae;color:#fff;padding:2px 6px;border-radius:4px}
.box{padding:13px 18px;border-radius:8px;margin-bottom:10px;font-size:14px}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
.warn{background:#fff8e1;border:1px solid #ffe082}
.err{background:#ffebee;border:1px solid #ef9a9a}
.info{background:#e3f2fd;border:1px solid #90caf9}
button{padding:11px 28px;border:none;border-radius:6px;font-size:15px;cursor:pointer;margin-top:10px;font-weight:bold}
.btn-g{background:#2e7d32;color:#fff}.btn-g:hover{background:#1b5e20}
code{background:#eee;padding:1px 5px;border-radius:3px;font-size:12px}
details summary{cursor:pointer;color:#1565c0}
</style></head><body>
<h2>Clover Rewards Member Import</h2>
<p style="color:#666">Merchant: <code><?= $MERCHANT_UUID ?></code> &nbsp;|&nbsp; Location: <code><?= $LOCATION_UUID ?></code></p>

<!-- Step 1: Token refresh -->
<h3>1. Token Refresh</h3>
<?php if ($SESSION_TOKEN): ?>
<div class="box ok">✅ Session token refreshed automatically. (expires ~1h from now)</div>
<?php else: ?>
<div class="box err">❌ Refresh failed (HTTP <?= $r1['code'] ?>). Raw: <code><?= htmlspecialchars(mb_substr($r1['raw']??'',0,200)) ?></code></div>
<?php endif; ?>

<!-- Step 2: Launch token -->
<h3>2. Launch Token</h3>
<?php if ($LAUNCH_TOKEN && $LAUNCH_TOKEN !== $SESSION_TOKEN): ?>
<div class="box ok">✅ Launch token obtained from product/launch endpoint.</div>
<?php elseif ($LAUNCH_TOKEN): ?>
<div class="box warn">⚠️ Using session token as launch token (launch endpoint did not return a separate token). HTTP <?= $launchData['code'] ?></div>
<details><summary>Launch response</summary><pre><?= htmlspecialchars(json_encode($launchData['data'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre></details>
<?php else: ?>
<div class="box err">❌ Could not get launch token. Session token failed or launch endpoint error.</div>
<?php endif; ?>

<!-- Step 3: Member endpoint probe -->
<h3>3. Member List Endpoint Probe</h3>
<?php if ($bestPath): ?>
<div class="box ok">
  ✅ <b>Working endpoint found!</b><br>
  Path: <code><?= htmlspecialchars($bestPath) ?></code><br>
  Token type: <b><?= $bestToken === $LAUNCH_TOKEN ? 'launch' : 'session' ?></b><br>
  Sample record: <code><?= htmlspecialchars(mb_substr(json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),0,400)) ?></code>
</div>
<?php else: ?>
<div class="box err">❌ No endpoint returned member data. See probe table below — share this page screenshot.</div>
<?php endif; ?>

<details><summary>Show all probe results (<?= count($allProbes) ?> endpoints tested)</summary>
<table style="margin-top:6px"><thead><tr><th>Token</th><th>Path</th><th>Status</th><th>Response preview</th></tr></thead><tbody>
<?php foreach ($allProbes as $k => $p):
    $r = $probeResults[$k] ?? ['code'=>0,'data'=>null];
    $isBest = $p['path']===$bestPath && $p['token']===$bestToken;
    $preview = $r['data'] ? json_encode($r['data'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : ($r['raw']??'');
?>
<tr <?= $isBest?"style='background:#e8f5e9;font-weight:bold'":($r['code']===404||$r['code']===405?"style='color:#bbb'":"") ?>>
  <td><small><?= $p['label'] ?></small></td>
  <td><code style="font-size:10px"><?= htmlspecialchars($p['path']) ?></code></td>
  <td><?= badge($r['code']) ?></td>
  <td><pre><?= htmlspecialchars(mb_substr($preview,0,300)) ?></pre></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</details>

<!-- Import result -->
<?php if ($importResult): ?>
<h3>4. Import Result</h3>
<div class="box ok">
  ✅ <b>Done!</b> &nbsp;
  Updated: <b><?= $importResult['updated'] ?></b> &nbsp;|&nbsp;
  Not in loyalty DB: <b><?= $importResult['notFound'] ?></b> &nbsp;|&nbsp;
  No phone: <b><?= $importResult['noPhone'] ?></b> &nbsp;|&nbsp;
  Total members: <b><?= $importResult['total'] ?></b>
</div>
<table><thead><tr><th>Customer</th><th>Clover Points</th><th>Result</th></tr></thead>
<tbody><?= $importResult['rows']?:'<tr><td colspan="3" style="color:#aaa">Nothing</td></tr>' ?></tbody>
</table>

<?php elseif ($bestPath): ?>
<h3>4. Import All Members</h3>
<div class="box info">
  Found member data at the working endpoint. Click below to import all pages into the loyalty database (points will be SET to match Clover — existing points will be overwritten).
</div>
<form method="POST">
  <input type="hidden" name="do_import" value="1">
  <button type="submit" class="btn-g">⬆ Import All <?= count($probeResults[array_key_first(array_filter($allProbes,fn($p)=>$p['path']===$bestPath&&$p['token']===$bestToken))]??[]) ?> Members Now</button>
</form>
<?php endif; ?>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_rewards_import.php from server after use.</p>
</body></html>
