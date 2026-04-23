<?php
require_once __DIR__ . '/auth.php';
$customer = refresh_customer();

$message = '';
$msgType = '';
$pointsAdded = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim(strtolower($_POST['code'] ?? ''));
    $code = preg_replace('/\s+/', ' ', $code); // normalize whitespace

    if (!$code) {
        $message = 'Vui lòng nhập mã từ receipt.';
        $msgType = 'error';
    } else {
        $db    = get_db();
        $nowMs = (int)(microtime(true) * 1000);

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM receipt_codes WHERE code = ? FOR UPDATE');
            $stmt->execute([$code]);
            $rc = $stmt->fetch();

            if (!$rc) {
                $db->rollBack();
                $message = 'Mã không hợp lệ. Vui lòng kiểm tra lại.';
                $msgType = 'error';
            } elseif ($rc['claimed_by'] !== null) {
                $db->rollBack();
                $message = 'Mã này đã được sử dụng rồi.';
                $msgType = 'error';
            } elseif ($rc['expires_at'] < $nowMs) {
                $db->rollBack();
                $message = 'Mã này đã hết hạn (expired ' . date('d/m/Y', intdiv($rc['expires_at'], 1000)) . ').';
                $msgType = 'error';
            } else {
                $pts       = (int)$rc['points'];
                $newPoints = (int)$customer['points'] + $pts;
                $tier      = tier_from_points($newPoints);

                $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
                   ->execute([$newPoints, $tier, $nowMs, $customer['id']]);

                $db->prepare('UPDATE receipt_codes SET claimed_by=?, claimed_at=? WHERE id=?')
                   ->execute([$customer['id'], $nowMs, $rc['id']]);

                $db->prepare(
                    'INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at)
                     VALUES (?,?,\'EARNED\',?,?,?)'
                )->execute([uuid4(), $customer['id'], $pts, 'Receipt code: ' . strtoupper($code), $nowMs]);

                $db->commit();

                $pointsAdded = $pts;
                $_SESSION['customer']['points'] = $newPoints;
                $_SESSION['customer']['tier']   = $tier;
                $customer['points'] = $newPoints;
                $customer['tier']   = $tier;

                $message = "Thành công! Bạn nhận được +{$pts} điểm. Tổng hiện tại: " . number_format($newPoints) . ' điểm.';
                $msgType = 'success';
            }
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Lỗi hệ thống. Vui lòng thử lại.';
            $msgType = 'error';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loyalte – Nhập mã receipt</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6fb; min-height: 100vh; }
  nav { background: #1a1a2e; color: #fff; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
  nav h1 { font-size: 1.4rem; font-weight: 800; }
  nav a { color: #ccc; text-decoration: none; font-size: .9rem; margin-left: 18px; }
  nav a:hover { color: #fff; }
  .container { max-width: 500px; margin: 40px auto; padding: 0 16px; }
  .card { background: #fff; border-radius: 16px; padding: 32px 28px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
  .card h2 { font-size: 1.3rem; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; }
  .card p { font-size: .9rem; color: #666; margin-bottom: 24px; }
  .pts-bar { background: #6c63ff; color: #fff; border-radius: 10px; padding: 12px 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
  .pts-bar span { font-size: .85rem; opacity: .85; }
  .pts-bar strong { font-size: 1.4rem; font-weight: 900; }
  label { display: block; font-size: .85rem; font-weight: 600; color: #444; margin-bottom: 6px; }
  input[type=text] { width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1.1rem; letter-spacing: 2px; text-transform: lowercase; outline: none; transition: border-color .2s; text-align: center; }
  input[type=text]:focus { border-color: #6c63ff; }
  .hint { font-size: .78rem; color: #999; margin-top: 8px; text-align: center; }
  .btn { display: block; width: 100%; margin-top: 20px; padding: 14px; background: #6c63ff; color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s; }
  .btn:hover { background: #574fd6; }
  .alert { padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; font-size: .9rem; line-height: 1.5; }
  .alert.success { background: #e8faf0; color: #1d7a45; border: 1px solid #a3dfc0; }
  .alert.error   { background: #fdecea; color: #c0392b; border: 1px solid #f5c6cb; }
  .receipt-icon { font-size: 3rem; text-align: center; margin-bottom: 12px; }
  .success-big { text-align: center; padding: 24px 0; }
  .success-big .pts { font-size: 3rem; font-weight: 900; color: #27ae60; }
  .back-link { display: block; text-align: center; margin-top: 20px; color: #6c63ff; text-decoration: none; font-size: .9rem; }
  .back-link:hover { text-decoration: underline; }
</style>
</head>
<body>
<nav>
  <h1>Loyalte</h1>
  <div>
    <a href="dashboard.php">Tổng quan</a>
    <a href="history.php">Lịch sử</a>
    <a href="rewards.php">Ưu đãi</a>
    <a href="logout.php">Đăng xuất</a>
  </div>
</nav>

<div class="container">
  <div class="pts-bar">
    <span>Điểm hiện tại</span>
    <strong><?= number_format((int)$customer['points']) ?></strong>
  </div>

  <div class="card">
    <div class="receipt-icon">🧾</div>

    <?php if ($msgType === 'success'): ?>
      <div class="success-big">
        <div class="pts">+<?= $pointsAdded ?></div>
        <p style="font-size:1.1rem;font-weight:700;color:#1a1a2e;margin:8px 0 4px">điểm đã được cộng!</p>
        <p style="color:#666;font-size:.9rem"><?= htmlspecialchars($message) ?></p>
      </div>
      <a href="dashboard.php" class="btn" style="text-decoration:none;display:block;text-align:center">Xem tổng quan</a>
    <?php else: ?>
      <h2>Nhập mã từ receipt</h2>
      <p>Nhập đúng mã ghi trên receipt của bạn để nhận điểm thưởng. Mỗi mã chỉ dùng được một lần.</p>

      <?php if ($message): ?>
      <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST">
        <label for="code">Mã receipt</label>
        <input type="text" id="code" name="code"
               placeholder="vd: apple river gold"
               autocomplete="off" autocorrect="off" spellcheck="false"
               value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
               autofocus required>
        <p class="hint">Nhập đúng như trên receipt (3 từ, phân cách bởi dấu cách)</p>
        <button type="submit" class="btn">Nhận điểm</button>
      </form>
    <?php endif; ?>
  </div>

  <a href="dashboard.php" class="back-link">← Quay lại tổng quan</a>
</div>
</body>
</html>
