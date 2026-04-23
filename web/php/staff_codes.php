<?php
// Staff-only page: generate & view receipt codes
// Access: http://yourserver/loyalteapp/web/php/staff_codes.php
// Protected by simple session-based staff login (separate from customer login)

session_start();
// Path assumes web/php/ and backend/ are siblings under the same project root
$root = dirname(dirname(__DIR__));
require_once $root . '/backend/config.php';
require_once $root . '/backend/db.php';
require_once $root . '/backend/helpers.php';

// ── Simple staff auth ─────────────────────────────────────────────────────────
$staffLoggedIn = !empty($_SESSION['staff_id']);

if (!$staffLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $db       = get_db();
    $stmt     = $db->prepare('SELECT id, password_hash, name FROM staff_accounts WHERE email = ?');
    $stmt->execute([$email]);
    $staff    = $stmt->fetch();
    if ($staff && password_verify($password, $staff['password_hash'])) {
        $_SESSION['staff_id']   = $staff['id'];
        $_SESSION['staff_name'] = $staff['name'];
        $staffLoggedIn = true;
    } else {
        $loginError = 'Sai email hoặc mật khẩu.';
    }
}

if (isset($_POST['staff_logout'])) {
    unset($_SESSION['staff_id'], $_SESSION['staff_name']);
    header('Location: staff_codes.php');
    exit;
}

// ── Generate code ─────────────────────────────────────────────────────────────
$generated  = null;
$genError   = null;

if ($staffLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $points     = (int)($_POST['points'] ?? 0);
    $expiryDays = (int)($_POST['expiry_days'] ?? 28);
    $note       = trim($_POST['note'] ?? '');

    if ($points <= 0) {
        $genError = 'Số điểm phải lớn hơn 0.';
    } else {
        $db    = get_db();
        $nowMs = (int)(microtime(true) * 1000);
        $expires = $nowMs + ($expiryDays * 86400000);
        $code  = generate_receipt_code($db);
        $rid   = uuid4();

        $db->prepare(
            'INSERT INTO receipt_codes (id,code,points,expires_at,created_by,created_at,note)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([$rid, $code, $points, $expires, $_SESSION['staff_id'], $nowMs, $note ?: null]);

        $generated = [
            'code'       => $code,
            'points'     => $points,
            'expires_at' => $expires,
            'note'       => $note,
        ];
    }
}

// ── Load recent codes ─────────────────────────────────────────────────────────
$codes = [];
if ($staffLoggedIn) {
    $db    = get_db();
    $stmt  = $db->query(
        'SELECT r.*, c.name AS claimed_by_name
         FROM receipt_codes r
         LEFT JOIN customers c ON c.id = r.claimed_by
         ORDER BY r.created_at DESC LIMIT 50'
    );
    $codes = $stmt->fetchAll();
}

function generate_receipt_code(PDO $db): string {
    $words = [
        'apple','brave','cloud','dance','eagle','flame','grape','happy','ivory','jazzy',
        'kite','lemon','maple','noble','ocean','pearl','queen','river','storm','tiger',
        'amber','blaze','coral','drift','ember','frost','glide','honey','india','jewel',
        'karma','laser','lunar','magic','nexus','olive','prism','quest','radar','solar',
        'tempo','vapor','windy','xenon','yacht','zebra','swift','polar','delta','echo',
    ];
    do {
        $code = $words[array_rand($words)] . ' '
              . $words[array_rand($words)] . ' '
              . $words[array_rand($words)];
        $s = $db->prepare('SELECT id FROM receipt_codes WHERE code = ?');
        $s->execute([$code]);
    } while ($s->fetch());
    return $code;
}

function uuid4(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loyalte Staff – Tạo mã receipt</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f2f8; min-height: 100vh; }
  nav { background: #1a1a2e; color: #fff; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
  nav h1 { font-size: 1.3rem; font-weight: 800; }
  nav span { font-size: .85rem; color: #aaa; }
  .container { max-width: 860px; margin: 30px auto; padding: 0 16px; display: grid; grid-template-columns: 340px 1fr; gap: 20px; }
  @media(max-width:680px){ .container { grid-template-columns: 1fr; } }
  .card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
  .card h2 { font-size: 1rem; font-weight: 700; color: #1a1a2e; margin-bottom: 18px; }
  label { display: block; font-size: .82rem; font-weight: 600; color: #555; margin-bottom: 5px; margin-top: 12px; }
  input, select { width: 100%; padding: 10px 12px; border: 1.5px solid #ddd; border-radius: 8px; font-size: .95rem; outline: none; }
  input:focus { border-color: #6c63ff; }
  .btn { width: 100%; margin-top: 16px; padding: 12px; background: #6c63ff; color: #fff; border: none; border-radius: 8px; font-size: .95rem; font-weight: 700; cursor: pointer; }
  .btn:hover { background: #574fd6; }
  .btn-red { background: #e74c3c; width: auto; padding: 6px 14px; font-size: .8rem; }
  .btn-red:hover { background: #c0392b; }
  .generated { background: #f0fff4; border: 2px dashed #27ae60; border-radius: 12px; padding: 20px; margin-bottom: 16px; text-align: center; }
  .generated .code-text { font-size: 1.5rem; font-weight: 900; color: #1a1a2e; letter-spacing: 2px; margin: 8px 0; font-family: monospace; }
  .generated .pts { color: #27ae60; font-size: 1rem; font-weight: 700; }
  .generated .exp { font-size: .78rem; color: #888; margin-top: 4px; }
  .alert.error { background: #fdecea; color: #c0392b; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; font-size: .88rem; }
  table { width: 100%; border-collapse: collapse; font-size: .83rem; }
  th { background: #f8f9fc; padding: 10px 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #eee; }
  td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; color: #333; }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
  .badge-ok    { background: #e8faf0; color: #27ae60; }
  .badge-used  { background: #fdecea; color: #e74c3c; }
  .badge-exp   { background: #fff3e0; color: #e67e22; }
  /* Login form */
  .login-wrap { max-width: 380px; margin: 80px auto; }
</style>
</head>
<body>
<nav>
  <h1>⭐ Loyalte Staff</h1>
  <?php if ($staffLoggedIn): ?>
  <span>
    Xin chào, <?= htmlspecialchars($_SESSION['staff_name']) ?> &nbsp;
    <form method="POST" style="display:inline">
      <button name="staff_logout" class="btn btn-red">Đăng xuất</button>
    </form>
  </span>
  <?php endif; ?>
</nav>

<?php if (!$staffLoggedIn): ?>
<!-- ── Login form ── -->
<div class="login-wrap">
  <div class="card">
    <h2 style="text-align:center;font-size:1.2rem;margin-bottom:20px">Đăng nhập Staff</h2>
    <?php if (!empty($loginError)): ?>
    <div class="alert error"><?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>
    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" required autofocus>
      <label>Mật khẩu</label>
      <input type="password" name="password" required>
      <button type="submit" name="staff_login" class="btn">Đăng nhập</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ── Main interface ── -->
<div class="container">

  <!-- Left: Generate -->
  <div>
    <div class="card">
      <h2>🧾 Tạo mã receipt mới</h2>

      <?php if ($generated): ?>
      <div class="generated">
        <div style="font-size:.85rem;color:#555">Mã receipt vừa tạo:</div>
        <div class="code-text"><?= htmlspecialchars($generated['code']) ?></div>
        <div class="pts">+<?= $generated['points'] ?> điểm</div>
        <div class="exp">Hết hạn: <?= date('d/m/Y', intdiv($generated['expires_at'], 1000)) ?></div>
        <?php if ($generated['note']): ?>
        <div style="font-size:.8rem;color:#777;margin-top:4px"><?= htmlspecialchars($generated['note']) ?></div>
        <?php endif; ?>
        <button onclick="navigator.clipboard.writeText('<?= $generated['code'] ?>')"
                style="margin-top:10px;padding:6px 16px;background:#6c63ff;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.82rem">
          📋 Copy mã
        </button>
      </div>
      <?php endif; ?>

      <?php if ($genError): ?>
      <div class="alert error"><?= htmlspecialchars($genError) ?></div>
      <?php endif; ?>

      <form method="POST">
        <label>Số điểm thưởng</label>
        <input type="number" name="points" min="1" max="10000"
               value="<?= (int)($_POST['points'] ?? 26) ?>" required>

        <label>Hạn sử dụng (ngày)</label>
        <select name="expiry_days">
          <option value="7">7 ngày</option>
          <option value="14">14 ngày</option>
          <option value="28" selected>28 ngày (1 tháng)</option>
          <option value="90">90 ngày (3 tháng)</option>
        </select>

        <label>Ghi chú (tùy chọn)</label>
        <input type="text" name="note" placeholder="vd: Hóa đơn #1234, bàn 5...">

        <button type="submit" name="generate" class="btn">Tạo mã mới</button>
      </form>
    </div>
  </div>

  <!-- Right: Code list -->
  <div class="card">
    <h2>📋 Lịch sử mã receipt (50 mã gần nhất)</h2>
    <?php
    $nowMs = (int)(microtime(true) * 1000);
    if (empty($codes)): ?>
    <p style="color:#999;font-size:.9rem;padding:20px 0">Chưa có mã nào được tạo.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <tr>
        <th>Mã</th>
        <th>Điểm</th>
        <th>Hết hạn</th>
        <th>Trạng thái</th>
        <th>Ghi chú</th>
      </tr>
      <?php foreach ($codes as $c):
        $expired  = $c['expires_at'] < $nowMs;
        $claimed  = $c['claimed_by'] !== null;
        if ($claimed) { $badge = 'badge-used';  $label = 'Đã dùng'; }
        elseif ($expired) { $badge = 'badge-exp'; $label = 'Hết hạn'; }
        else { $badge = 'badge-ok'; $label = 'Còn dùng được'; }
      ?>
      <tr>
        <td><code><?= htmlspecialchars($c['code']) ?></code></td>
        <td>+<?= $c['points'] ?></td>
        <td><?= date('d/m/Y', intdiv((int)$c['expires_at'], 1000)) ?></td>
        <td>
          <span class="badge <?= $badge ?>"><?= $label ?></span>
          <?php if ($claimed): ?>
          <div style="font-size:.72rem;color:#888;margin-top:2px"><?= htmlspecialchars($c['claimed_by_name'] ?? '') ?></div>
          <?php endif; ?>
        </td>
        <td style="color:#888;font-size:.8rem"><?= htmlspecialchars($c['note'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
  </div>

</div>
<?php endif; ?>
</body>
</html>
