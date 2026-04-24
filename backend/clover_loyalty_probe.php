<?php
// Probe every possible Clover loyalty/rewards endpoint
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

if (!$token || !$mId) die('<h2 style="color:red">❌ No config saved in DB.</h2>');

function probe_url(string $base, string $token, string $method, string $path): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $data = ($body && $body !== '') ? @json_decode($body, true) : null;
    return ['code' => $code, 'data' => $data, 'raw' => $body, 'err' => $err];
}

// Get 3 sample customers (with full expansions) for per-customer probes
$custR     = probe_url($base, $token, 'GET', "/v3/merchants/{$mId}/customers?expand=phoneNumbers,emailAddresses,customerAttributes,metadata&limit=3");
$customers = $custR['data']['elements'] ?? [];
$sid       = $customers[0]['id'] ?? '';   // sample customer ID

$appId = '1EVSVRM8SV8RC';   // Clover Promos & Rewards client_id

// ── All endpoint probes ────────────────────────────────────────────────────────
$probes = [
    // 1. Customer attributes (loyalty points often stored here per app)
    ['GET',  "/v3/merchants/{$mId}/customers?expand=customerAttributes&limit=3",        'Customers + customerAttributes expand'],
    ['GET',  "/v3/merchants/{$mId}/customer_attributes?limit=10",                        'customer_attributes resource'],
    $sid ? ['GET', "/v3/merchants/{$mId}/customers/{$sid}?expand=customerAttributes,metadata", 'Single customer + customerAttributes'] : null,
    $sid ? ['GET', "/v3/merchants/{$mId}/customers/{$sid}/attributes",                   'Customer /attributes sub-resource'] : null,
    $sid ? ['GET', "/v3/merchants/{$mId}/customers/{$sid}/credits",                      'Customer /credits'] : null,
    $sid ? ['GET', "/v3/merchants/{$mId}/customers/{$sid}/rewards",                      'Customer /rewards'] : null,

    // 2. Standard /v3/loyalty/ namespace
    ['GET',  "/v3/loyalty/merchants/{$mId}/points",                                       'loyalty/points'],
    ['GET',  "/v3/loyalty/merchants/{$mId}/schemes",                                      'loyalty/schemes'],
    ['GET',  "/v3/loyalty/merchants/{$mId}/programs",                                     'loyalty/programs'],
    ['GET',  "/v3/loyalty/merchants/{$mId}/members?limit=5",                              'loyalty/members'],
    ['GET',  "/v3/loyalty/merchants/{$mId}/rewards?limit=5",                              'loyalty/rewards'],
    ['GET',  "/v3/loyalty/merchants/{$mId}/events?limit=5",                               'loyalty/events'],
    ['GET',  "/v3/loyalty/merchants/{$mId}/tiers",                                        'loyalty/tiers'],
    ['GET',  "/v3/loyalty/merchants/{$mId}/transactions?limit=5",                         'loyalty/transactions'],

    // 3. App-scoped endpoints (using Rewards app client_id)
    ['GET',  "/v3/apps/{$appId}/merchants/{$mId}",                                        'apps/{appId}/merchants/{mId}'],
    ['GET',  "/v3/apps/{$appId}/merchants/{$mId}/points",                                 'apps/{appId}/points'],
    ['GET',  "/v3/apps/{$appId}/merchants/{$mId}/customers?limit=3",                      'apps/{appId}/customers'],
    ['GET',  "/v3/apps/{$appId}/merchants/{$mId}/members?limit=3",                        'apps/{appId}/members'],
    ['GET',  "/v3/apps/{$appId}/merchants/{$mId}/rewards?limit=3",                        'apps/{appId}/rewards'],
    ['GET',  "/v3/apps/{$appId}/merchants/{$mId}/events?limit=3",                         'apps/{appId}/events'],
    $sid ? ['GET', "/v3/apps/{$appId}/merchants/{$mId}/customers/{$sid}",                 'apps/{appId}/customer by id'] : null,
    $sid ? ['GET', "/v3/apps/{$appId}/merchants/{$mId}/customers/{$sid}/points",          'apps/{appId}/customer/points'] : null,

    // 4. /v3/merchants/ promo / reward variants
    ['GET',  "/v3/merchants/{$mId}/promos?limit=5",                                       'merchants/promos'],
    ['GET',  "/v3/merchants/{$mId}/promotions?limit=5",                                   'merchants/promotions'],
    ['GET',  "/v3/merchants/{$mId}/reward_cards?limit=5",                                 'merchants/reward_cards'],
    ['GET',  "/v3/merchants/{$mId}/reward_programs",                                      'merchants/reward_programs'],
    ['GET',  "/v3/merchants/{$mId}/rewards?limit=5",                                      'merchants/rewards'],
    ['GET',  "/v3/merchants/{$mId}/loyalty_events?limit=5",                               'merchants/loyalty_events'],
    ['GET',  "/v3/merchants/{$mId}/loyalty_members?limit=5",                              'merchants/loyalty_members'],
    ['GET',  "/v3/merchants/{$mId}/credit_cards?limit=5",                                 'merchants/credit_cards'],
    ['GET',  "/v3/merchants/{$mId}/customer_credits?limit=5",                             'merchants/customer_credits'],

    // 5. Merchant info (check installed apps list)
    ['GET',  "/v3/merchants/{$mId}?expand=apps",                                          'merchant + apps expand'],
    ['GET',  "/v3/merchants/{$mId}/apps",                                                  'merchants/apps'],

    // 6. Alternate base paths seen in some Clover integrations
    ['GET',  "/v2/merchant/{$mId}/loyalty_events?limit=5",                                'v2 loyalty_events'],
    ['GET',  "/v2/merchant/{$mId}/reward_cards?limit=5",                                  'v2 reward_cards'],
];

$probes = array_values(array_filter($probes));   // remove nulls

// Run all probes (curl_multi for speed)
$results = [];
$remaining = $probes;
$chunkSize = 10;

foreach (array_chunk($remaining, $chunkSize, true) as $chunk) {
    $mh = curl_multi_init();
    $handles = [];
    foreach ($chunk as $k => $p) {
        [$method, $path] = $p;
        $ch = curl_init($base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
        ]);
        $handles[$k] = $ch;
        curl_multi_add_handle($mh, $ch);
    }
    do { $s = curl_multi_exec($mh, $active); if ($active) curl_multi_select($mh); }
    while ($active && $s == CURLM_OK);
    foreach ($handles as $k => $ch) {
        $body = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $results[$k] = [
            'code' => $code,
            'data' => ($body && $body !== '') ? @json_decode($body, true) : null,
            'raw'  => $body,
        ];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

// Classify results
function badge(int $code): string {
    if ($code === 200) return "<span class='b200'>200 ✅ DATA!</span>";
    if ($code === 401) return "<span class='b401'>401 Auth</span>";
    if ($code === 403) return "<span class='b403'>403 Forbidden</span>";
    if ($code === 404) return "<span class='b404'>404 Not Found</span>";
    if ($code === 405) return "<span class='b405'>405 No Method</span>";
    return "<span class='bx'>{$code}</span>";
}

$found200 = false;
foreach ($results as $k => $r) { if ($r['code'] === 200) { $found200 = true; break; } }
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Loyalty Probe</title>
<style>
  body{font-family:sans-serif;padding:24px;max-width:1100px;background:#fafafa}
  h2{margin-bottom:4px}
  table{width:100%;border-collapse:collapse;margin-top:12px;font-size:13px}
  th,td{padding:7px 10px;border:1px solid #ddd;text-align:left;vertical-align:top}
  th{background:#f0f0f0;font-size:12px}
  pre{margin:0;font-size:11px;max-height:180px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
  .b200{background:#2e7d32;color:#fff;padding:2px 7px;border-radius:4px;font-weight:bold}
  .b401{background:#e57373;color:#fff;padding:2px 7px;border-radius:4px}
  .b403{background:#ef9a9a;color:#333;padding:2px 7px;border-radius:4px}
  .b404{background:#bdbdbd;color:#333;padding:2px 7px;border-radius:4px}
  .b405{background:#ffa726;color:#fff;padding:2px 7px;border-radius:4px}
  .bx  {background:#90a4ae;color:#fff;padding:2px 7px;border-radius:4px}
  .box{padding:14px 18px;border-radius:8px;margin-bottom:14px;font-size:14px}
  .warn{background:#fff8e1;border:1px solid #ffe082}
  .info{background:#e3f2fd;border:1px solid #90caf9}
  .ok  {background:#e8f5e9;border:1px solid #a5d6a7}
  code{background:#eee;padding:1px 5px;border-radius:3px;font-size:12px}
  h3{color:#1565c0;margin-top:28px}
  details summary{cursor:pointer;color:#1565c0;font-weight:bold}
</style>
</head><body>
<h2>Clover Loyalty API Probe</h2>
<p style="color:#666">Merchant: <b><?= htmlspecialchars($mId) ?></b> &nbsp;|&nbsp; Environment: <b><?= htmlspecialchars($env) ?></b> &nbsp;|&nbsp; Sample customer ID: <b><?= htmlspecialchars($sid ?: '—') ?></b></p>

<?php if (!$found200): ?>
<div class="box warn">
  <b>⚠️ No endpoint returned HTTP 200 with your current token.</b><br>
  This means the Clover Promos &amp; Rewards data is locked behind the Rewards app's own OAuth token — your merchant API token doesn't have access.<br><br>
  <b>Best way to get the data: use your browser to intercept the Rewards dashboard API calls.</b> See the instructions below the results table.
</div>
<?php else: ?>
<div class="box ok">
  <b>✅ Found working endpoints!</b> Check the green rows below.
</div>
<?php endif; ?>

<table>
  <thead><tr><th>Method</th><th>Path</th><th>Description</th><th>Status</th><th>Response preview</th></tr></thead>
  <tbody>
  <?php foreach ($probes as $k => $p):
      [$method, $path, $desc] = $p;
      $r = $results[$k] ?? ['code'=>0,'data'=>null,'raw'=>''];
      $preview = $r['data'] ? json_encode($r['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : ($r['raw'] ?: '(empty)');
      $rowStyle = $r['code'] === 200 ? 'background:#f1f8e9' : ($r['code'] === 404 ? 'color:#aaa' : '');
  ?>
  <tr style="<?= $rowStyle ?>">
    <td><?= $method ?></td>
    <td><code><?= htmlspecialchars($path) ?></code></td>
    <td><?= htmlspecialchars($desc) ?></td>
    <td><?= badge($r['code']) ?></td>
    <td><pre><?= htmlspecialchars(mb_substr($preview, 0, 600)) ?></pre></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- ── Browser DevTools instructions ───────────────────────────────────────── -->
<h3>🔍 How to intercept the Rewards dashboard API calls</h3>
<div class="box info">
  <p>The Clover Rewards dashboard (<code>www.clover.com/rewards</code>) talks to an API using your <b>browser login session</b>. You can capture those calls like this:</p>
  <ol style="line-height:2">
    <li>Open <b><a href="https://www.clover.com/rewards/overview?client_id=1EVSVRM8SV8RC" target="_blank">www.clover.com/rewards/overview?client_id=1EVSVRM8SV8RC</a></b> while logged in as the merchant.</li>
    <li>Press <b>F12</b> (or right-click → Inspect) to open DevTools.</li>
    <li>Click the <b>Network</b> tab. Click the <b>Fetch/XHR</b> filter button.</li>
    <li>Press <b>F5</b> to reload the page. A list of API calls will appear.</li>
    <li>Click on any call that looks like <code>api.clover.com/...</code> or <code>clover.com/v3/...</code>.</li>
    <li>In the right panel → <b>Headers</b> tab → copy the full <b>Request URL</b> and the <b>Authorization</b> (or <b>Cookie</b>) header value.</li>
    <li>Also click the <b>History</b> tab on the Rewards page and capture those network calls too — they likely show per-customer point totals.</li>
  </ol>
  <p><b>Share those URLs + headers with your developer</b> — they can then replicate the export in PHP to pull all 1,113 customers' points into your database automatically.</p>
</div>

<h3>📧 Alternative: Request a data export from Clover Support</h3>
<div class="box warn">
  <p>Email or chat <b>support@clover.com</b> and ask:</p>
  <blockquote style="border-left:3px solid #ffa726;padding-left:12px;color:#555;font-style:italic">
    "We are migrating from Clover Promos &amp; Rewards to a custom loyalty system.
    Could you please provide a CSV export of all customer loyalty point balances
    for merchant ID <b><?= htmlspecialchars($mId) ?></b>?
    We need: customer name, phone number, and current point balance."
  </blockquote>
  <p>Clover support can usually export this data within 1–2 business days.</p>
</div>

<h3>📋 Customers with customerAttributes (raw)</h3>
<details><summary>Show first 3 customers (expand=customerAttributes)</summary>
<pre style="margin-top:8px;background:#fff;border:1px solid #ddd;padding:12px;border-radius:6px;font-size:12px"><?= htmlspecialchars(json_encode($custR['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
</details>

<p style="color:#bbb;margin-top:30px;font-size:12px">⚠️ Delete clover_loyalty_probe.php from server after use.</p>
</body></html>
