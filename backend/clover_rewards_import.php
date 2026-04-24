<?php
// Clover Rewards (Perka / customer-engagement API) member import
// IDs & tokens captured from browser DevTools on 2026-04-24
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
set_time_limit(300);
header('Content-Type: text/html; charset=utf-8');

$db    = get_db();
$nowMs = (int)(microtime(true) * 1000);

// ── Known IDs (from DevTools JSON) ───────────────────────────────────────────
$MERCHANT_UUID = '5037be2d-0bd7-4f41-8707-68f9c2014d37';
$LOCATION_UUID = 'c11c519a-407a-4ae0-9815-ce97e8691b26';
$PROGRAM_UUID  = '0020168f-ec91-4f72-89e9-6fad1da43319';
$REFRESH_TOKEN = 'UgaJl8vu9N3S7_Ef_5-12nGXXhMTwLrEalyLXt2ozZK4pDRtn6IkC3sOsjyP1QWc';
$LAUNCH_TOKEN  = 'SZiQwVXf0dQoK9RGAIZSRj5kbSGHBuHb3xnH8ba_hoymr6kRKRdQxSx7Y9nU2e8Z';
$CE_BASE       = 'https://api.clover.com/customer-engagement/1';

// Session token — user can override via form if expired
$SESSION_TOKEN = trim($_POST['session_token']
    ?? 'SsG2QcB0ZWllePnoh73xKtUifo0iBhGXLX4WQTg4exg2IPJSyGi6OKyw2_CwF_wf');

$dryRun = ($_POST['dry_run'] ?? '1') !== '0';
$doImport = isset($_POST['do_import']);

// ── HTTP helpers ──────────────────────────────────────────────────────────────
function ce_get(string $base, string $token, string $path): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => ($body ? json_decode($body, true) : null), 'raw' => $body];
}

function ce_post(string $base, string $token, string $path, array $payload): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => ($body ? json_decode($body, true) : null), 'raw' => $body];
}

// ── Step 1: Try to refresh the session token ──────────────────────────────────
$refreshed = false;
$refreshError = '';
$refreshAttempts = [
    // Common Perka / CE refresh endpoints
    ['POST', '/social/token',           ['refreshToken' => $REFRESH_TOKEN]],
    ['POST', '/social/auth/token',      ['refreshToken' => $REFRESH_TOKEN]],
    ['POST', '/perka/auth/refresh',     ['refreshToken' => $REFRESH_TOKEN]],
    ['POST', '/clover/token/refresh',   ['refreshToken' => $REFRESH_TOKEN]],
    ['GET',  '/social/token?refreshToken=' . urlencode($REFRESH_TOKEN), []],
    ['GET',  '/perka/token?refreshToken=' . urlencode($REFRESH_TOKEN) . '&merchantUuid=' . $MERCHANT_UUID, []],
];

$newToken = null;
$refreshLog = [];
foreach ($refreshAttempts as [$method, $path, $payload]) {
    if ($method === 'POST') {
        $r = ce_post($CE_BASE, $REFRESH_TOKEN, $path, $payload);
    } else {
        $r = ce_get($CE_BASE, $REFRESH_TOKEN, $path);
    }
    $refreshLog[] = ['method'=>$method,'path'=>$path,'code'=>$r['code'],'data'=>$r['data']];
    if ($r['code'] === 200 && !empty($r['data'])) {
        // Look for a session token in response
        $d = $r['data'];
        $tok = $d['token'] ?? $d['sessionToken'] ?? $d['access_token'] ??
               $d['data']['authorizationToken'][0]['token'] ?? null;
        if (!$tok && isset($d['data']['authorizationToken'])) {
            foreach ($d['data']['authorizationToken'] as $t) {
                if (($t['type'] ?? '') === 'SESSION') { $tok = $t['token']; break; }
            }
        }
        if ($tok) {
            $newToken   = $tok;
            $refreshed  = true;
            $SESSION_TOKEN = $tok;
            break;
        }
    }
}

// ── Step 2: Probe member list endpoints ───────────────────────────────────────
$memberEndpoints = [
    "/social/groups/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/social/groups/{$MERCHANT_UUID}/memberships?page=0&pageSize=999",
    "/clover/merchants/{$MERCHANT_UUID}/members?page=0&pageSize=999",
    "/clover/merchants/{$MERCHANT_UUID}/memberships?page=0&pageSize=999",
    "/clover/locations/{$LOCATION_UUID}/members?page=0&pageSize=999",
    "/clover/locations/{$LOCATION_UUID}/memberships?page=0&pageSize=999",
    "/perka/merchants/{$MERCHANT_UUID}/members?page=0&pageSize=999",
    "/perka/merchants/{$LOCATION_UUID}/members?page=0&pageSize=999",
    "/social/programs/{$PROGRAM_UUID}/memberships?page=0&pageSize=999",
    "/clover/programs/{$PROGRAM_UUID}/members?page=0&pageSize=999",
    // Try with launch token too
];

// Run probes in parallel (two token variants)
$allProbes = [];
foreach ($memberEndpoints as $path) {
    $allProbes[] = ['token' => $SESSION_TOKEN, 'label' => 'session', 'path' => $path];
    $allProbes[] = ['token' => $LAUNCH_TOKEN,  'label' => 'launch',  'path' => $path];
}

$mh = curl_multi_init();
$handles = [];
foreach ($allProbes as $k => $p) {
    $ch = curl_init($CE_BASE . $p['path']);
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
do { $s = curl_multi_exec($mh, $active); if ($active) curl_multi_select($mh); }
while ($active && $s == CURLM_OK);
$probeResults = [];
foreach ($handles as $k => $ch) {
    $body = curl_multi_getcontent($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $probeResults[$k] = ['code' => $code, 'data' => ($body ? json_decode($body, true) : null)];
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// ── Find best working endpoint ────────────────────────────────────────────────
$bestEndpoint = null;
$bestToken    = null;
$members      = [];

foreach ($allProbes as $k => $p) {
    $r = $probeResults[$k];
    if ($r['code'] !== 200 || empty($r['data'])) continue;
    $d = $r['data'];
    // Look for array of member-like objects
    $els = $d['memberships'] ?? $d['members'] ?? $d['elements'] ?? $d['data'] ?? null;
    if (is_array($els) && count($els) > 0) {
        $sample = $els[0];
        // Must have some kind of phone or points field
        if (isset($sample['points']) || isset($sample['balance']) || isset($sample['phone']) ||
            isset($sample['phoneNumber']) || isset($sample['loyaltyPoints']) || isset($sample['user'])) {
            $bestEndpoint = $p['path'];
            $bestToken    = $p['token'];
            $members      = $els;
            break;
        }
    }
    // Also accept flat array at root
    if (is_array($d) && !isset($d['code']) && count($d) > 0 && isset($d[0])) {
        $sample = $d[0];
        if (isset($sample['points']) || isset($sample['phone']) || isset($sample['user'])) {
            $bestEndpoint = $p['path'];
            $bestToken    = $p['token'];
            $members      = $d;
            break;
        }
    }
}

// ── Step 3: Full import if requested and endpoint found ───────────────────────
$importResult = null;
if ($doImport && $bestEndpoint && !$dryRun) {
    $updated = $notFound = $total = 0;
    $rows = '';
    $page = 0;
    $pageSize = 500;

    do {
        $pagePath = preg_replace('/page=\d+/', "page={$page}", $bestEndpoint);
        $pagePath = preg_replace('/pageSize=\d+/', "pageSize={$pageSize}", $pagePath);
        $r = ce_get($CE_BASE, $bestToken, $pagePath);
        $d = $r['data'] ?? [];
        $batch = $d['memberships'] ?? $d['members'] ?? $d['elements'] ?? $d['data'] ?? (is_array($d) ? $d : []);
        if (!is_array($batch)) break;

        foreach ($batch as $m) {
            $total++;
            // Extract phone — field names vary
            $rawPhone = preg_replace('/\D/', '',
                $m['phone'] ?? $m['phoneNumber'] ??
                $m['user']['phoneNumber'] ?? $m['user']['phone'] ?? '');
            // Extract points
            $pts = (int)(
                $m['points'] ?? $m['balance'] ?? $m['loyaltyPoints'] ??
                $m['user']['points'] ?? $m['pointBalance'] ?? 0
            );
            $name = trim(
                ($m['firstName'] ?? $m['user']['firstName'] ?? '') . ' ' .
                ($m['lastName']  ?? $m['user']['lastName']  ?? '')
            ) ?: ($m['name'] ?? $m['user']['name'] ?? '');

            if (!$rawPhone) {
                $notFound++;
                $rows .= "<tr><td style='color:#aaa'>—</td><td>$pts</td><td style='color:#aaa'>No phone</td></tr>";
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
            $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
               ->execute([uuid4(), $cust['id'], $pts, 'Clover Rewards import ' . date('Y-m-d'), $nowMs]);
            $updated++;
            $rows .= "<tr><td>" . htmlspecialchars($name ?: $rawPhone) . "</td><td>$pts</td><td style='color:green'>✅ Set to <b>$pts</b> pts ($tier)</td></tr>";
        }

        $page++;
    } while (count($batch) === $pageSize && $page < 20);

    $importResult = compact('updated', 'notFound', 'total', 'rows');
}

function badge(int $c): string {
    if ($c===200) return "<span class='b200'>200 ✅</span>";
    if ($c===401) return "<span class='b401'>401</span>";
    if ($c===403) return "<span class='b403'>403</span>";
    if ($c===404) return "<span class='b404'>404</span>";
    if ($c===405) return "<span class='b405'>405</span>";
    return "<span class='bx'>$c</span>";
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Rewards Import</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:1000px;background:#fafafa}
h2,h3{margin-bottom:4px}h3{color:#1565c0;margin-top:24px}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px}
th,td{padding:6px 10px;border:1px solid #ddd;vertical-align:top;text-align:left}
th{background:#f0f0f0}
pre{margin:0;font-size:11px;max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
.b200{background:#2e7d32;color:#fff;padding:2px 6px;border-radius:4px;font-weight:bold}
.b401{background:#e57373;color:#fff;padding:2px 6px;border-radius:4px}
.b403{background:#ef9a9a;color:#333;padding:2px 6px;border-radius:4px}
.b404{background:#bdbdbd;color:#333;padding:2px 6px;border-radius:4px}
.b405{background:#ffa726;color:#fff;padding:2px 6px;border-radius:4px}
.bx{background:#90a4ae;color:#fff;padding:2px 6px;border-radius:4px}
.box{padding:13px 18px;border-radius:8px;margin-bottom:12px;font-size:14px}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
.warn{background:#fff8e1;border:1px solid #ffe082}
.err{background:#ffebee;border:1px solid #ef9a9a}
.info{background:#e3f2fd;border:1px solid #90caf9}
button{padding:10px 26px;border:none;border-radius:6px;font-size:15px;cursor:pointer;margin-top:12px}
.btn-g{background:#2e7d32;color:#fff}.btn-g:hover{background:#1b5e20}
.btn-b{background:#1565c0;color:#fff}.btn-b:hover{background:#0d47a1}
code{background:#eee;padding:1px 5px;border-radius:3px;font-size:12px}
textarea{width:100%;height:50px;font-family:monospace;font-size:12px;padding:8px;box-sizing:border-box;border:1px solid #ccc;border-radius:4px}
label{display:block;font-weight:bold;font-size:13px;margin-top:12px}
details summary{cursor:pointer;color:#1565c0}
</style></head><body>
<h2>Clover Rewards Member Import</h2>
<p style="color:#666">API: <code><?= $CE_BASE ?></code> &nbsp;|&nbsp; Merchant UUID: <code><?= $MERCHANT_UUID ?></code> &nbsp;|&nbsp; Location UUID: <code><?= $LOCATION_UUID ?></code></p>

<!-- Token refresh status -->
<h3>1. Session Token Refresh</h3>
<?php if ($refreshed): ?>
<div class="box ok">✅ Token refreshed successfully! New session token obtained automatically.</div>
<?php else: ?>
<div class="box warn">
  ⚠️ Could not auto-refresh token — using the pasted session token.<br>
  The session token expires 1 hour after you got it from DevTools (~<?= date('H:i', strtotime('2026-04-24 23:14:06') + 3600) ?> UTC).
  If you see 401 errors below, paste a fresh token using the form at the bottom.
</div>
<?php endif; ?>
<details><summary>Show refresh attempt log</summary>
<table style="margin-top:6px"><thead><tr><th>Method</th><th>Path</th><th>Status</th><th>Response</th></tr></thead><tbody>
<?php foreach ($refreshLog as $rl): ?>
<tr <?= $rl['code']===200?'style="background:#f1f8e9"':'' ?>>
  <td><?= $rl['method'] ?></td>
  <td><code style="font-size:11px"><?= htmlspecialchars($rl['path']) ?></code></td>
  <td><?= badge($rl['code']) ?></td>
  <td><pre><?= htmlspecialchars(mb_substr(json_encode($rl['data'],JSON_PRETTY_PRINT),0,300)) ?></pre></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</details>

<!-- Member endpoint probes -->
<h3>2. Member List Endpoint Probe</h3>
<?php if ($bestEndpoint): ?>
<div class="box ok">
  ✅ Found working endpoint! <code><?= htmlspecialchars($bestEndpoint) ?></code><br>
  Members found in this response: <b><?= count($members) ?></b><br>
  Sample: <code><?= htmlspecialchars(mb_substr(json_encode($members[0] ?? [], JSON_PRETTY_PRINT), 0, 300)) ?></code>
</div>
<?php else: ?>
<div class="box err">
  ❌ No working member endpoint found yet. Try refreshing the session token below.
</div>
<?php endif; ?>

<table><thead><tr><th>Token</th><th>Path</th><th>Status</th><th>Response preview</th></tr></thead><tbody>
<?php foreach ($allProbes as $k => $p):
    $r = $probeResults[$k] ?? ['code'=>0,'data'=>null];
    $preview = $r['data'] ? json_encode($r['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '—';
    $isBest = $p['path'] === $bestEndpoint && $p['token'] === $bestToken;
?>
<tr <?= $isBest ? "style='background:#e8f5e9;font-weight:bold'" : ($r['code']===404||$r['code']===405 ? "style='color:#bbb'" : '') ?>>
  <td><small><?= $p['label'] ?></small></td>
  <td><code style="font-size:11px"><?= htmlspecialchars($p['path']) ?></code></td>
  <td><?= badge($r['code']) ?></td>
  <td><pre><?= htmlspecialchars(mb_substr($preview, 0, 400)) ?></pre></td>
</tr>
<?php endforeach; ?>
</tbody></table>

<!-- Import section -->
<?php if ($importResult): ?>
<h3>3. Import Result</h3>
<div class="box ok">
  ✅ <b>Import complete!</b><br>
  Updated: <b><?= $importResult['updated'] ?></b> &nbsp;|&nbsp;
  Not found in loyalty DB: <b><?= $importResult['notFound'] ?></b> &nbsp;|&nbsp;
  Total members: <b><?= $importResult['total'] ?></b>
</div>
<table><thead><tr><th>Customer</th><th>Clover Points</th><th>Result</th></tr></thead>
<tbody><?= $importResult['rows'] ?: '<tr><td colspan="3" style="color:#aaa">Nothing</td></tr>' ?></tbody>
</table>
<?php elseif ($bestEndpoint): ?>
<h3>3. Import</h3>
<div class="box info">
  Found <b><?= count($members) ?></b> members in probe. Click below to import all pages into the loyalty database.
</div>
<form method="POST">
  <input type="hidden" name="session_token" value="<?= htmlspecialchars($SESSION_TOKEN) ?>">
  <input type="hidden" name="dry_run" value="0">
  <input type="hidden" name="do_import" value="1">
  <button type="submit" class="btn-g">⬆ Import All Members Now</button>
</form>
<?php endif; ?>

<!-- Manual token override -->
<h3>Paste Fresh Session Token (if expired)</h3>
<form method="POST">
  <label>Session Token (paste new one from DevTools)</label>
  <textarea name="session_token" placeholder="SsG2QcB0..."><?= htmlspecialchars($SESSION_TOKEN) ?></textarea>
  <input type="hidden" name="dry_run" value="1">
  <button type="submit" class="btn-b">🔄 Re-probe with this token</button>
</form>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_rewards_import.php after use — it contains merchant credentials.</p>
</body></html>
