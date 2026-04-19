<?php
require_once __DIR__ . '/auth.php';
$customer = refresh_customer();

$tierColors = [
    'BRONZE'   => ['bg' => '#cd7f32', 'label' => 'Đồng'],
    'SILVER'   => ['bg' => '#b0b0b0', 'label' => 'Bạc'],
    'GOLD'     => ['bg' => '#ffd700', 'label' => 'Vàng'],
    'PLATINUM' => ['bg' => '#9b89b0', 'label' => 'Bạch Kim'],
];
$tierMins = ['BRONZE' => 0, 'SILVER' => 500, 'GOLD' => 1000, 'PLATINUM' => 2500];
$tierNext = ['BRONZE' => 500, 'SILVER' => 1000, 'GOLD' => 2500, 'PLATINUM' => null];

$tier      = $customer['tier'];
$points    = (int)$customer['points'];
$color     = $tierColors[$tier] ?? $tierColors['BRONZE'];
$nextMin   = $tierNext[$tier];
$curMin    = $tierMins[$tier];
$progress  = $nextMin ? min(100, round(($points - $curMin) / ($nextMin - $curMin) * 100)) : 100;

// Recent 5 transactions
$db   = get_db();
$stmt = $db->prepare('SELECT * FROM loyalty_transactions WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$customer['id']]);
$txns = $stmt->fetchAll();

function fmt_date(int $ms): string {
    return date('d/m/Y', intdiv($ms, 1000));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loyalte – Tổng quan</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6fb; min-height: 100vh; }
  nav { background: #1a1a2e; color: #fff; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
  nav h1 { font-size: 1.4rem; font-weight: 800; }
  nav a { color: #ccc; text-decoration: none; font-size: .9rem; margin-left: 18px; }
  nav a:hover { color: #fff; }
  .container { max-width: 680px; margin: 30px auto; padding: 0 16px; }
  .points-card { background: <?= $color['bg'] ?>; color: #fff; border-radius: 18px; padding: 30px 28px; margin-bottom: 20px; position: relative; overflow: hidden; }
  .points-card h2 { font-size: .95rem; opacity: .85; text-transform: uppercase; letter-spacing: 1px; }
  .points-card .pts { font-size: 3.2rem; font-weight: 900; line-height: 1; margin: 8px 0 4px; }
  .points-card .tier-badge { display: inline-block; background: rgba(255,255,255,.25); border-radius: 20px; padding: 4px 14px; font-size: .8rem; font-weight: 700; }
  .progress-wrap { margin-top: 18px; }
  .progress-wrap p { font-size: .8rem; opacity: .85; margin-bottom: 6px; }
  .progress-bar { background: rgba(0,0,0,.2); border-radius: 100px; height: 8px; }
  .progress-fill { background: #fff; border-radius: 100px; height: 8px; width: <?= $progress ?>%; }
  .qr-section { text-align: center; background: #fff; border-radius: 14px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
  .qr-section h3 { font-size: .95rem; color: #444; margin-bottom: 14px; }
  .qr-section img { width: 180px; height: 180px; }
  .qr-section p { font-size: .8rem; color: #888; margin-top: 10px; }
  .card { background: #fff; border-radius: 14px; padding: 20px 22px; box-shadow: 0 2px 12px rgba(0,0,0,.06); margin-bottom: 20px; }
  .card h3 { font-size: 1rem; font-weight: 700; color: #1a1a2e; margin-bottom: 14px; }
  .txn { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
  .txn:last-child { border-bottom: none; }
  .txn-left p { font-size: .9rem; color: #333; }
  .txn-left span { font-size: .78rem; color: #999; }
  .txn-pts { font-weight: 700; font-size: 1rem; }
  .earned  { color: #27ae60; }
  .redeemed { color: #e74c3c; }
  .adjusted { color: #2980b9; }
  .nav-links { display: flex; gap: 12px; margin-bottom: 20px; }
  .nav-links a { flex: 1; text-align: center; padding: 11px; background: #fff; border-radius: 10px; text-decoration: none; color: #6c63ff; font-weight: 600; font-size: .88rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
  .nav-links a:hover { background: #6c63ff; color: #fff; }
</style>
</head>
<body>
<nav>
  <h1>Loyalte</h1>
  <div>
    <a href="history.php">Lịch sử</a>
    <a href="rewards.php">Ưu đãi</a>
    <a href="logout.php">Đăng xuất</a>
  </div>
</nav>

<div class="container">
  <!-- Points card -->
  <div class="points-card">
    <h2>Điểm tích lũy</h2>
    <div class="pts"><?= number_format($points) ?></div>
    <span class="tier-badge"><?= $color['label'] ?></span>
    <?php if ($nextMin): ?>
    <div class="progress-wrap">
      <p>Còn <?= number_format($nextMin - $points) ?> điểm để lên hạng tiếp theo</p>
      <div class="progress-bar"><div class="progress-fill"></div></div>
    </div>
    <?php else: ?>
    <div class="progress-wrap"><p>Bạn đang ở hạng cao nhất!</p></div>
    <?php endif; ?>
  </div>

  <!-- Quick nav -->
  <div class="nav-links">
    <a href="history.php">Lịch sử điểm</a>
    <a href="rewards.php">Đổi ưu đãi</a>
  </div>

  <!-- QR Code -->
  <div class="qr-section">
    <h3>Mã QR thành viên của bạn</h3>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($customer['qr_code']) ?>" alt="QR Code">
    <p><?= htmlspecialchars($customer['member_id']) ?> • <?= htmlspecialchars($customer['name']) ?></p>
  </div>

  <!-- Recent transactions -->
  <div class="card">
    <h3>Giao dịch gần đây</h3>
    <?php if (empty($txns)): ?>
    <p style="color:#999;font-size:.9rem;">Chưa có giao dịch nào.</p>
    <?php else: foreach ($txns as $t):
      $pts = (int)$t['points'];
      $cls = strtolower($t['type']);
      $sign = $pts > 0 ? '+' : '';
    ?>
    <div class="txn">
      <div class="txn-left">
        <p><?= htmlspecialchars($t['description']) ?></p>
        <span><?= fmt_date((int)$t['created_at']) ?></span>
      </div>
      <div class="txn-pts <?= $cls ?>"><?= $sign . number_format($pts) ?></div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>
</body>
</html>
