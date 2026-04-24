<?php
// Show all installed apps + try loyalty endpoints with each app's real UUID
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=utf-8');

$db  = get_db();
$cfg = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
$token = $cfg['access_token'] ?? '';
$mId   = $cfg['merchant_id']  ?? '';
$env   = $cfg['environment']  ?? 'sandbox';
$base  = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';

if (!$token || !$mId) die('<h2 style="color:red">No config.</h2>');

function cget(string $base, string $token, string $path): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code'=>$code, 'data'=>($body ? json_decode($body, true) : null), 'raw'=>$body];
}

// ── Fetch ALL apps (paginate) ─────────────────────────────────────────────────
$allApps = [];
$offset  = 0;
do {
    $r = cget($base, $token, "/v3/merchants/{$mId}/apps?limit=50&offset={$offset}");
    $els = $r['data']['elements'] ?? [];
    $allApps = array_merge($allApps, $els);
    $offset += 50;
} while (count($els) === 50 && $offset < 500);

// ── Fetch ALL customers (first 200) to find ones WITH attributes ──────────────
$custR = cget($base, $token, "/v3/merchants/{$mId}/customers?expand=customerAttributes,phoneNumbers&limit=10");
$customers = $custR['data']['elements'] ?? [];

// ── Try loyalty endpoints with each installed app UUID ────────────────────────
$appProbes = [];
foreach ($allApps as $app) {
    $aId = $app['id'];
    $aName = $app['name'] ?? $aId;
    foreach ([
        "/v3/apps/{$aId}/merchants/{$mId}",
        "/v3/apps/{$aId}/merchants/{$mId}/customers?limit=3",
        "/v3/apps/{$aId}/merchants/{$mId}/points",
        "/v3/apps/{$aId}/merchants/{$mId}/members?limit=3",
    ] as $path) {
        $appProbes[] = ['app' => $aName, 'appId' => $aId, 'path' => $path];
    }
}

// Run probes in parallel
$probeResults = [];
$chunkSize = 10;
foreach (array_chunk($appProbes, $chunkSize, true) as $chunk) {
    $mh = curl_multi_init();
    $handles = [];
    foreach ($chunk as $k => $p) {
        $ch = curl_init($base . $p['path']);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
        $handles[$k] = $ch;
        curl_multi_add_handle($mh, $ch);
    }
    do { $s = curl_multi_exec($mh, $active); if ($active) curl_multi_select($mh); }
    while ($active && $s == CURLM_OK);
    foreach ($handles as $k => $ch) {
        $body = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $probeResults[$k] = ['code'=>$code, 'data'=>($body ? json_decode($body, true) : null)];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

function badge(int $c): string {
    if ($c===200) return "<span class='b200'>200 ✅ DATA</span>";
    if ($c===401) return "<span class='b401'>401</span>";
    if ($c===403) return "<span class='b403'>403</span>";
    if ($c===404) return "<span class='b404'>404</span>";
    if ($c===405) return "<span class='b405'>405</span>";
    return "<span class='bx'>$c</span>";
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Apps & Loyalty Deep Probe</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:1100px;background:#fafafa}
h2,h3{margin-bottom:4px}h3{color:#1565c0;margin-top:28px}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px}
th,td{padding:6px 10px;border:1px solid #ddd;text-align:left;vertical-align:top}
th{background:#f0f0f0}
pre{margin:0;font-size:11px;max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
.b200{background:#2e7d32;color:#fff;padding:2px 7px;border-radius:4px;font-weight:bold}
.b401{background:#e57373;color:#fff;padding:2px 6px;border-radius:4px}
.b403{background:#ef9a9a;color:#333;padding:2px 6px;border-radius:4px}
.b404{background:#bdbdbd;color:#333;padding:2px 6px;border-radius:4px}
.b405{background:#ffa726;color:#fff;padding:2px 6px;border-radius:4px}
.bx{background:#90a4ae;color:#fff;padding:2px 6px;border-radius:4px}
.box{padding:13px 18px;border-radius:8px;margin-bottom:12px;font-size:14px}
.info{background:#e3f2fd;border:1px solid #90caf9}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
code{background:#eee;padding:1px 5px;border-radius:3px;font-size:12px}
details summary{cursor:pointer;color:#1565c0;font-weight:bold;margin-top:8px}
</style></head><body>
<h2>Clover Apps &amp; Loyalty Deep Probe</h2>
<p style="color:#666">Merchant: <b><?= htmlspecialchars($mId) ?></b> &nbsp;|&nbsp; <?= count($allApps) ?> apps installed</p>

<!-- ── 1. All installed apps ───────────────────────────────────────────────── -->
<h3>1. All Installed Apps</h3>
<div class="box info">Looking for the <b>Promos &amp; Rewards</b> or <b>Loyalty</b> app — its UUID is different from the client_id <code>1EVSVRM8SV8RC</code>.</div>
<table>
  <thead><tr><th>App ID (UUID)</th><th>Name</th><th>Published</th><th>Developer</th></tr></thead>
  <tbody>
  <?php foreach ($allApps as $app): ?>
  <tr <?= (stripos($app['name']??'','reward')!==false||stripos($app['name']??'','promo')!==false||stripos($app['name']??'','loyal')!==false) ? "style='background:#fff9c4;font-weight:bold'" : '' ?>>
    <td><code><?= htmlspecialchars($app['id']??'') ?></code></td>
    <td><?= htmlspecialchars($app['name']??'') ?></td>
    <td><?= ($app['published']??false) ? '✅' : '—' ?></td>
    <td><?= htmlspecialchars($app['developer']['name']??'') ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$allApps): ?><tr><td colspan="4" style="color:#aaa">No apps returned</td></tr><?php endif; ?>
  </tbody>
</table>

<!-- ── 2. Per-app loyalty endpoint probes ─────────────────────────────────── -->
<h3>2. Loyalty Endpoint Probes — One Per Installed App UUID</h3>
<table>
  <thead><tr><th>App</th><th>Path</th><th>Status</th><th>Response</th></tr></thead>
  <tbody>
  <?php foreach ($appProbes as $k => $p):
      $r = $probeResults[$k] ?? ['code'=>0,'data'=>null];
      $preview = $r['data'] ? json_encode($r['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '—';
      $row = $r['code']===200 ? 'background:#f1f8e9' : ($r['code']===404 ? 'color:#bbb' : '');
  ?>
  <tr style="<?= $row ?>">
    <td><?= htmlspecialchars($p['app']) ?><br><small style="color:#999"><?= htmlspecialchars($p['appId']) ?></small></td>
    <td><code style="font-size:11px"><?= htmlspecialchars($p['path']) ?></code></td>
    <td><?= badge($r['code']) ?></td>
    <td><pre><?= htmlspecialchars(mb_substr($preview,0,400)) ?></pre></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- ── 3. Customer customerAttributes raw ─────────────────────────────────── -->
<h3>3. Customers with customerAttributes expand (first 10)</h3>
<details><summary>Show raw JSON</summary>
<pre style="margin-top:8px;background:#fff;border:1px solid #ddd;padding:12px;border-radius:6px;font-size:12px;max-height:400px;overflow-y:auto"><?= htmlspecialchars(json_encode($custR['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
</details>

<?php
// Count how many customers have non-empty customerAttributes
$withAttrs = 0;
foreach ($customers as $c) {
    if (!empty($c['customerAttributes']['elements'])) $withAttrs++;
}
?>
<p>Customers with non-empty customerAttributes: <b><?= $withAttrs ?> / <?= count($customers) ?></b></p>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_apps_full.php from server after use.</p>
</body></html>
