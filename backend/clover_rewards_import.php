<?php
// Clover Rewards (CE /card/ namespace) — find member endpoint & import
// DELETE after use!
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
set_time_limit(600);
header('Content-Type: text/html; charset=utf-8');

$db    = get_db();
$nowMs = (int)(microtime(true) * 1000);

$MERCHANT_UUID = '5037be2d-0bd7-4f41-8707-68f9c2014d37';
$LOCATION_UUID = 'c11c519a-407a-4ae0-9815-ce97e8691b26';
$CE_BASE       = 'https://api.clover.com/customer-engagement/1';

// Sample customer UUIDs from history (for per-customer profile probes)
$SAMPLE_CUUIDS = [
    '0503c2fd-720c-48f8-bc98-7723a1bcecee',
    '92ca7745-4d9e-4c9b-a62d-32979ffbf025',  // Carlos Felix
    '637e9da8-0a2f-4e8d-95d0-9fd37cc8e7c5',  // Shaquan Dean
];

$TOKEN = trim($_POST['token'] ?? '');
$mode  = $_POST['mode'] ?? 'probe';   // probe | history_import

function ce_get(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Accept: application/json']]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code'=>$code,'data'=>($raw?json_decode($raw,true):null),'raw'=>$raw];
}

// ── Probe candidate member endpoints in /card/ namespace ─────────────────────
$probeResults = [];
$importResult = null;

if ($TOKEN && $mode === 'probe') {
    $paths = [
        // Member list candidates
        "/card/merchant/members/{$MERCHANT_UUID}?page=0&pageSize=100",
        "/card/merchant/memberships/{$MERCHANT_UUID}?page=0&pageSize=100",
        "/card/merchant/{$MERCHANT_UUID}?page=0&pageSize=100",
        "/card/members/{$MERCHANT_UUID}?page=0&pageSize=100",
        "/card/memberships/{$MERCHANT_UUID}?page=0&pageSize=100",
        "/card/merchant/members/{$LOCATION_UUID}?page=0&pageSize=100",
        "/card/location/members/{$LOCATION_UUID}?page=0&pageSize=100",
        "/card/location/{$LOCATION_UUID}?page=0&pageSize=100",
        // Customer profile (single)
        "/card/customer/{$SAMPLE_CUUIDS[0]}",
        "/card/customer/{$SAMPLE_CUUIDS[1]}",
        "/card/customer/{$SAMPLE_CUUIDS[2]}",
        // Balance / points
        "/card/customer/{$SAMPLE_CUUIDS[0]}/balance",
        "/card/customer/{$SAMPLE_CUUIDS[0]}/points",
        // Stats
        "/card/merchant/stats/{$MERCHANT_UUID}",
        "/card/merchant/overview/{$MERCHANT_UUID}",
        // History (verify token still works)
        "/card/merchant/history/{$MERCHANT_UUID}?page=0",
    ];

    $mh = curl_multi_init();
    $handles = [];
    foreach ($paths as $k => $p) {
        $ch = curl_init($CE_BASE . $p);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$TOKEN,'Accept: application/json']]);
        $handles[$k] = $ch;
        curl_multi_add_handle($mh, $ch);
    }
    do { $s = curl_multi_exec($mh, $act); if ($act) curl_multi_select($mh); }
    while ($act && $s == CURLM_OK);
    foreach ($handles as $k => $ch) {
        $raw = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $probeResults[$k] = ['path'=>$paths[$k],'code'=>$code,
            'data'=>($raw?json_decode($raw,true):null),'raw'=>$raw];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

// ── History-based import: page through all history, accumulate points per customerUuid ──
// Then fetch each customer's profile for phone + current balance
if ($TOKEN && $mode === 'history_import') {
    $historyBase = $CE_BASE . "/card/merchant/history/{$MERCHANT_UUID}";

    // Step 1: collect all transactions → customerUuid → sum points & get name
    $customerTotals = [];   // [uuid => ['name'=>..., 'points'=>..., 'email'=>...]]
    $page = 0;
    $totalTx = 0;

    do {
        $r = ce_get($historyBase . "?page={$page}", $TOKEN);
        if ($r['code'] !== 200) break;
        $txs     = $r['data']['data']['historyTransaction'] ?? [];
        $hasMore = ($r['data']['data']['merchantHistoryPage'][0]['hasNextPage'] ?? false);

        foreach ($txs as $tx) {
            $uuid = $tx['customerUuid'] ?? '';
            if (!$uuid) continue;
            $totalTx++;

            $pts = 0;
            foreach ($tx['pointsNumbers'] ?? [] as $p) $pts += (int)$p;
            // Subtract redemptions
            foreach ($tx['redemptions'] ?? [] as $red) {
                if (isset($red['pointsNumbers'])) foreach ($red['pointsNumbers'] as $p) $pts -= abs((int)$p);
            }

            if (!isset($customerTotals[$uuid])) {
                $customerTotals[$uuid] = ['name'=>'', 'points'=>0, 'email'=>'', 'phone_hint'=>''];
            }
            $customerTotals[$uuid]['points'] += $pts;

            // Try to extract email from customerName like "John Doe john@email.com"
            $cname = $tx['customerName'] ?? '';
            $customerTotals[$uuid]['name'] = $cname;
            if (preg_match('/[\w.+-]+@[\w.-]+\.\w+/', $cname, $em)) {
                $customerTotals[$uuid]['email'] = $em[0];
            }
            // Store partial phone hint (last 4 digits)
            if (preg_match('/\+\d+\*+(\d{4})/', $cname, $ph)) {
                $customerTotals[$uuid]['phone_hint'] = $ph[1];
            }
        }

        $page++;
    } while ($hasMore && $page < 500);

    // Step 2: try to fetch individual customer profiles for full phone
    // Try the profile endpoint for each customer (in batches of 20)
    $profilePaths = [];
    foreach (array_keys($customerTotals) as $uuid) {
        $profilePaths[$uuid] = "/card/customer/{$uuid}";
    }

    $phoneLookup = [];   // [uuid => full_phone]
    $batchSize   = 20;
    foreach (array_chunk(array_keys($profilePaths), $batchSize, true) as $batch) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($batch as $uuid) {
            $ch = curl_init($CE_BASE . $profilePaths[$uuid]);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10,
                CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$TOKEN,'Accept: application/json']]);
            $handles[$uuid] = $ch;
            curl_multi_add_handle($mh, $ch);
        }
        do { $s = curl_multi_exec($mh, $act); if ($act) curl_multi_select($mh); }
        while ($act && $s == CURLM_OK);
        foreach ($handles as $uuid => $ch) {
            $raw = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 200 && $raw) {
                $d = json_decode($raw, true);
                // Extract phone from profile
                $phone = preg_replace('/\D/', '',
                    $d['phoneNumber'] ?? $d['phone'] ?? $d['mobile'] ??
                    ($d['data']['consumer']['phone'] ?? $d['data']['phoneNumber'] ?? ''));
                if ($phone) $phoneLookup[$uuid] = $phone;
                // Also check email
                $email = $d['email'] ?? $d['emailAddress'] ?? $d['data']['consumer']['email'] ?? '';
                if ($email && !isset($customerTotals[$uuid]['profile_email'])) {
                    $customerTotals[$uuid]['profile_email'] = $email;
                }
                // Check current balance in profile
                $bal = $d['points'] ?? $d['balance'] ?? $d['pointBalance'] ??
                       ($d['data']['points'] ?? null);
                if ($bal !== null) $customerTotals[$uuid]['profile_points'] = (int)$bal;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        usleep(100000); // 0.1s between batches to be polite
    }

    // Step 3: match to loyalty DB
    $updated = $notFound = $noPhone = $emailMatch = 0;
    $rows = '';

    foreach ($customerTotals as $uuid => $info) {
        // Use profile_points if available (more accurate than accumulated history)
        $pts = $info['profile_points'] ?? $info['points'];

        // Try phone from profile first
        $rawPhone = $phoneLookup[$uuid] ?? '';

        // Try email match if no phone
        $cust = null;
        if ($rawPhone) {
            $stmt = $db->prepare('SELECT id,name,points FROM customers WHERE phone=? OR phone=? LIMIT 1');
            $stmt->execute([$rawPhone, ltrim($rawPhone,'1')]);
            $cust = $stmt->fetch();
        }
        if (!$cust) {
            $email = $info['profile_email'] ?? $info['email'] ?? '';
            if ($email) {
                $stmt = $db->prepare('SELECT id,name,points FROM customers WHERE email=? LIMIT 1');
                $stmt->execute([$email]);
                $cust = $stmt->fetch();
                if ($cust) $emailMatch++;
            }
        }

        if (!$rawPhone && !($info['email'] ?? '') && !($info['profile_email'] ?? '')) {
            $noPhone++;
            $rows .= "<tr><td>".htmlspecialchars($info['name'])."</td><td>$pts</td><td style='color:#aaa'>No phone or email</td></tr>";
            continue;
        }

        if (!$cust) {
            $notFound++;
            $id = $rawPhone ?: ($info['email'] ?? $info['profile_email'] ?? '—');
            $rows .= "<tr><td>".htmlspecialchars($info['name'])."</td><td>$pts</td><td style='color:orange'>".htmlspecialchars($id)." not in loyalty DB</td></tr>";
            continue;
        }

        if ($pts < 0) $pts = 0;
        $tier = tier_from_points($pts);
        $db->prepare('UPDATE customers SET points=?,tier=?,updated_at=? WHERE id=?')->execute([$pts,$tier,$nowMs,$cust['id']]);
        if ($pts > 0) {
            $db->prepare('INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at) VALUES (?,?,"EARNED",?,?,?)')
               ->execute([uuid4(),$cust['id'],$pts,'Clover Rewards history import '.date('Y-m-d'),$nowMs]);
        }
        $updated++;
        $color = $pts > 0 ? '#2e7d32' : '#888';
        $matchType = $rawPhone ? "phone" : "email";
        $rows .= "<tr><td>".htmlspecialchars($cust['name'])."</td><td>$pts</td><td style='color:$color'>✅ $pts pts ($tier) via $matchType</td></tr>";
    }

    $importResult = compact('updated','notFound','noPhone','emailMatch','totalTx','rows');
    $importResult['totalCustomers'] = count($customerTotals);
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clover Rewards Import</title>
<style>
body{font-family:sans-serif;padding:24px;max-width:1020px;background:#fafafa}
h2,h3{margin-bottom:4px}h3{color:#1565c0;margin-top:22px}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px}
th,td{padding:5px 9px;border:1px solid #ddd;vertical-align:top;text-align:left}th{background:#f0f0f0}
pre{margin:0;font-size:11px;max-height:180px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
.box{padding:12px 17px;border-radius:7px;margin-bottom:10px;font-size:14px}
.ok{background:#e8f5e9;border:1px solid #a5d6a7}
.warn{background:#fff8e1;border:1px solid #ffe082}
.err{background:#ffebee;border:1px solid #ef9a9a}
.info{background:#e3f2fd;border:1px solid #90caf9}
button{padding:10px 24px;border:none;border-radius:6px;font-size:14px;cursor:pointer;font-weight:bold;margin:6px 6px 0 0}
.btn-b{background:#1565c0;color:#fff}.btn-g{background:#2e7d32;color:#fff}.btn-o{background:#e65100;color:#fff}
label{display:block;font-weight:bold;font-size:13px;margin-top:12px}
textarea{width:100%;height:50px;font-family:monospace;font-size:12px;padding:8px;box-sizing:border-box;border:1px solid #ccc;border-radius:4px}
code{background:#eee;padding:1px 5px;border-radius:3px;font-size:12px}
details summary{cursor:pointer;color:#1565c0}
</style></head><body>
<h2>Clover Rewards Member Import</h2>
<p style="color:#666">Merchant: <code><?= $MERCHANT_UUID ?></code></p>

<div class="box info">
  <b>How to get token from DevTools:</b><br>
  Open Rewards dashboard → F12 → Network → F5 → click any request → copy <code>Authorization: Bearer xxxxx</code>
  (use the history request: <code>history/5037be2d...?page=0</code>)
</div>

<!-- ── Probe results ──────────────────────────────────────────────────────── -->
<?php if ($probeResults): ?>
<h3>Member Endpoint Probe (<?= count($probeResults) ?> paths)</h3>
<?php $any200 = array_filter($probeResults, fn($r)=>$r['code']===200 && !empty($r['data'])); ?>
<?php if ($any200): ?>
<div class="box ok">✅ Some endpoints returned data! See green rows — look for member/phone data.</div>
<?php else: ?>
<div class="box warn">⚠️ Only history endpoint returned 200. Member list not found yet via direct endpoint.<br>
→ Use <b>History-based Import</b> below which reconstructs balances from all transactions.</div>
<?php endif; ?>
<table><thead><tr><th>Path</th><th>HTTP</th><th>Data preview</th></tr></thead><tbody>
<?php foreach ($probeResults as $r):
    $preview = $r['data'] ? json_encode($r['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : $r['raw'];
    $style = $r['code']===200?'background:#e8f5e9':($r['code']===404?'color:#bbb':'');
?>
<tr style="<?= $style ?>">
  <td><code style="font-size:10px"><?= htmlspecialchars($r['path']) ?></code></td>
  <td><b><?= $r['code'] ?></b></td>
  <td><pre><?= htmlspecialchars(mb_substr($preview??'',0,400)) ?></pre></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<!-- ── Import result ──────────────────────────────────────────────────────── -->
<?php if ($importResult): ?>
<h3>Import Result</h3>
<div class="box ok">
  ✅ <b>Done!</b><br>
  Unique customers in history: <b><?= $importResult['totalCustomers'] ?></b> &nbsp;|&nbsp;
  Total transactions: <b><?= $importResult['totalTx'] ?></b><br>
  Updated in loyalty DB: <b><?= $importResult['updated'] ?></b> &nbsp;|&nbsp;
  Via email match: <b><?= $importResult['emailMatch'] ?></b> &nbsp;|&nbsp;
  Not found: <b><?= $importResult['notFound'] ?></b> &nbsp;|&nbsp;
  No phone/email: <b><?= $importResult['noPhone'] ?></b>
</div>
<table><thead><tr><th>Customer</th><th>Points</th><th>Result</th></tr></thead>
<tbody><?= $importResult['rows']?:'<tr><td colspan="3" style="color:#aaa">Nothing</td></tr>' ?></tbody>
</table>
<?php endif; ?>

<!-- ── Form ─────────────────────────────────────────────────────────────────── -->
<form method="POST" style="margin-top:20px">
  <label>Bearer Token (from DevTools — history request Authorization header)</label>
  <textarea name="token" required placeholder="XOAtWYLjwIw7-uInrUs3Sn..."><?= htmlspecialchars($TOKEN) ?></textarea>

  <input type="hidden" name="mode" id="modeInput" value="probe">

  <button type="submit" class="btn-b" onclick="document.getElementById('modeInput').value='probe'">
    🔍 Probe — find member endpoint
  </button>

  <button type="submit" class="btn-o" onclick="document.getElementById('modeInput').value='history_import'"
    onclick="return confirm('This will page through ALL transaction history and accumulate points per customer. May take 2-3 minutes. Continue?')">
    📜 History Import — reconstruct balances from all transactions
  </button>
</form>

<div class="box warn" style="margin-top:14px">
  <b>📜 History Import</b> works by paging through ALL transactions, summing points per customer,
  then looking up full phone numbers from customer profiles. Use this if no direct member endpoint is found.<br>
  ⚠️ Token must stay valid for the entire run (~2-3 minutes). Get a fresh token first.
</div>

<p style="color:#bbb;margin-top:24px;font-size:12px">⚠️ Delete clover_rewards_import.php from server after use.</p>
</body></html>
