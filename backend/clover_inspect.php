<?php
// Inspect Clover customer data — find where reward points are stored
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: text/html; charset=utf-8');

$db  = get_db();
$cfg = $db->query('SELECT config_key, config_val FROM clover_config')->fetchAll(PDO::FETCH_KEY_PAIR);
$token = $cfg['access_token'] ?? '';
$mId   = $cfg['merchant_id']  ?? '';
$env   = $cfg['environment']  ?? 'sandbox';
$base  = ($env === 'production') ? 'https://api.clover.com' : 'https://apisandbox.dev.clover.com';

if (!$token || !$mId) die('<h2 style="color:red">❌ No config saved.</h2>');

function clover_get(string $base, string $token, string $path): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token, 'Accept: application/json']]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($body, true)];
}

// Fetch 3 customers with ALL expansions
$r = clover_get($base, $token,
    "/v3/merchants/{$mId}/customers?expand=phoneNumbers,emailAddresses,customerAttributes,addresses,metadata&limit=3");

// Also try loyalty-specific endpoints
$loyaltyPoints = clover_get($base, $token, "/v3/loyalty/merchants/{$mId}/points");
$rewardCards   = clover_get($base, $token, "/v3/merchants/{$mId}/reward_cards?limit=3");
$loyaltyEvents = clover_get($base, $token, "/v3/merchants/{$mId}/loyalty_events?limit=3");

?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Data Inspector</title>
<style>
  body{font-family:monospace;padding:20px;max-width:1100px;background:#fafafa}
  h2{font-family:sans-serif}
  h3{font-family:sans-serif;margin-top:30px;color:#1565c0}
  pre{background:#fff;border:1px solid #ddd;padding:16px;border-radius:6px;overflow-x:auto;font-size:12px;white-space:pre-wrap;word-break:break-all}
  .label{font-family:sans-serif;font-size:13px;color:#666;margin-bottom:4px}
  .ok{color:green;font-family:sans-serif}
  .err{color:red;font-family:sans-serif}
</style>
</head><body>
<h2>Clover Data Inspector</h2>
<p style="font-family:sans-serif;color:#666">Showing raw API responses to find where rewards points are stored.</p>

<h3>1. Customers (with all expansions)</h3>
<div class="label">
  HTTP <?= $r['code'] ?>
  <?= $r['code'] === 200 ? '<span class="ok">✅ OK</span>' : '<span class="err">❌ Error</span>' ?>
</div>
<pre><?= htmlspecialchars(json_encode($r['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>

<h3>2. Loyalty Points endpoint <code>/v3/loyalty/merchants/{mId}/points</code></h3>
<div class="label">HTTP <?= $loyaltyPoints['code'] ?></div>
<pre><?= htmlspecialchars(json_encode($loyaltyPoints['data'], JSON_PRETTY_PRINT)) ?></pre>

<h3>3. Reward Cards <code>/v3/merchants/{mId}/reward_cards</code></h3>
<div class="label">HTTP <?= $rewardCards['code'] ?></div>
<pre><?= htmlspecialchars(json_encode($rewardCards['data'], JSON_PRETTY_PRINT)) ?></pre>

<h3>4. Loyalty Events <code>/v3/merchants/{mId}/loyalty_events</code></h3>
<div class="label">HTTP <?= $loyaltyEvents['code'] ?></div>
<pre><?= htmlspecialchars(json_encode($loyaltyEvents['data'], JSON_PRETTY_PRINT)) ?></pre>

<p style="font-family:sans-serif;color:#bbb;margin-top:30px">⚠️ Delete clover_inspect.php after use.</p>
</body></html>
