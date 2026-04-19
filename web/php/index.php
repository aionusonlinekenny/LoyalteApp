<?php
require_once __DIR__ . '/auth.php';
start_session();

// Already logged in — go to dashboard
if (get_customer()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');

    // Basic normalization: keep digits, allow leading +
    $phone = preg_replace('/[^\d+]/', '', $phone);

    // Accept 10-digit US number → add +1
    if (preg_match('/^\d{10}$/', $phone)) {
        $phone = '+1' . $phone;
    }

    if (!preg_match('/^\+\d{7,15}$/', $phone)) {
        $error = 'Vui lòng nhập số điện thoại hợp lệ.';
    } else {
        $db   = get_db();
        $stmt = $db->prepare('SELECT * FROM customers WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $customer = $stmt->fetch();

        if ($customer) {
            login_customer($customer);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Không tìm thấy tài khoản với số điện thoại này.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loyalte – Đăng nhập</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6fb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.10); padding: 40px 36px; width: 100%; max-width: 400px; }
  .logo { text-align: center; margin-bottom: 28px; }
  .logo h1 { font-size: 2rem; font-weight: 800; color: #1a1a2e; letter-spacing: -0.5px; }
  .logo p { color: #666; margin-top: 4px; font-size: .9rem; }
  label { display: block; font-size: .85rem; font-weight: 600; color: #333; margin-bottom: 6px; }
  input[type=tel] { width: 100%; padding: 12px 14px; border: 1.5px solid #ddd; border-radius: 10px; font-size: 1rem; outline: none; transition: border-color .2s; }
  input[type=tel]:focus { border-color: #6c63ff; }
  .btn { display: block; width: 100%; margin-top: 18px; padding: 13px; background: #6c63ff; color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s; }
  .btn:hover { background: #574fd6; }
  .error { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6cb; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: .88rem; }
  .hint { text-align: center; margin-top: 20px; font-size: .8rem; color: #999; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>Loyalte</h1>
    <p>Xem điểm tích lũy của bạn</p>
  </div>

  <?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="phone">Số điện thoại</label>
    <input type="tel" id="phone" name="phone" placeholder="+84901234567 hoặc 0901234567" autofocus required>
    <button type="submit" class="btn">Xem điểm của tôi</button>
  </form>

  <p class="hint">Nhập số điện thoại đã đăng ký thẻ thành viên</p>
</div>
</body>
</html>
