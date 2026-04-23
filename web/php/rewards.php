<?php
require_once __DIR__ . '/auth.php';
$customer = refresh_customer();

$db = get_db();

$message = '';
$msgType = '';

// Handle redemption POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rewardId = $_POST['reward_id'] ?? '';
    if ($rewardId) {
        $stmtR = $db->prepare('SELECT * FROM rewards WHERE id = ? AND is_active = 1');
        $stmtR->execute([$rewardId]);
        $reward = $stmtR->fetch();

        if (!$reward) {
            $message = 'Ưu đãi không tồn tại hoặc đã hết hạn.';
            $msgType = 'error';
        } elseif ((int)$customer['points'] < (int)$reward['points_required']) {
            $message = sprintf(
                'Không đủ điểm. Bạn cần %s điểm, hiện có %s điểm.',
                number_format((int)$reward['points_required']),
                number_format((int)$customer['points'])
            );
            $msgType = 'error';
        } else {
            $nowMs     = (int)(microtime(true) * 1000);
            $newPoints = (int)$customer['points'] - (int)$reward['points_required'];
            $tier      = tier_from_points($newPoints);

            $db->beginTransaction();
            try {
                $db->prepare('UPDATE customers SET points=?, tier=?, updated_at=? WHERE id=?')
                   ->execute([$newPoints, $tier, $nowMs, $customer['id']]);

                $db->prepare(
                    'INSERT INTO loyalty_transactions (id,customer_id,type,points,description,created_at)
                     VALUES (?,?,\'REDEEMED\',?,?,?)'
                )->execute([uuid4(), $customer['id'], -(int)$reward['points_required'], 'Redeemed: ' . $reward['name'], $nowMs]);

                $db->prepare(
                    'INSERT INTO redemptions (id,customer_id,reward_id,points_used,redeemed_at)
                     VALUES (?,?,?,?,?)'
                )->execute([uuid4(), $customer['id'], $rewardId, (int)$reward['points_required'], $nowMs]);

                $db->commit();

                $_SESSION['customer']['points'] = $newPoints;
                $_SESSION['customer']['tier']   = $tier;
                $customer['points'] = $newPoints;
                $customer['tier']   = $tier;

                $message = 'Đổi thành công! Vui lòng cho nhân viên xem thông báo này.';
                $msgType = 'success';
            } catch (Exception $e) {
                $db->rollBack();
                $message = 'Lỗi xử lý. Vui lòng thử lại.';
                $msgType = 'error';
            }
        }
    }
}

function uuid4(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function tier_from_points(int $points): string {
    if ($points >= 2500) return 'PLATINUM';
    if ($points >= 1000) return 'GOLD';
    if ($points >= 500)  return 'SILVER';
    return 'BRONZE';
}

// Load rewards
$stmt = $db->query('SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required');
$rewards = $stmt->fetchAll();

$catLabels = ['FOOD' => '🍽 Ẩm thực', 'DRINK' => '☕ Đồ uống', 'DISCOUNT' => '🏷 Giảm giá', 'OTHER' => '🎁 Khác'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loyalte – Ưu đãi</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6fb; min-height: 100vh; }
  nav { background: #1a1a2e; color: #fff; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
  nav h1 { font-size: 1.4rem; font-weight: 800; }
  nav a { color: #ccc; text-decoration: none; font-size: .9rem; margin-left: 18px; }
  nav a:hover { color: #fff; }
  .container { max-width: 680px; margin: 30px auto; padding: 0 16px; }
  .pts-bar { background: #6c63ff; color: #fff; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
  .pts-bar span { font-size: .85rem; opacity: .85; }
  .pts-bar strong { font-size: 1.6rem; font-weight: 900; }
  .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-size: .9rem; }
  .alert.success { background: #e8faf0; color: #1d7a45; border: 1px solid #a3dfc0; }
  .alert.error   { background: #fdecea; color: #c0392b; border: 1px solid #f5c6cb; }
  .reward-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media(max-width:480px){ .reward-grid { grid-template-columns: 1fr; } }
  .reward-card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 2px 10px rgba(0,0,0,.06); display: flex; flex-direction: column; }
  .reward-cat { font-size: .72rem; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
  .reward-name { font-size: 1rem; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
  .reward-desc { font-size: .82rem; color: #666; flex: 1; }
  .reward-pts { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; }
  .pts-badge { background: #f0eeff; color: #6c63ff; border-radius: 20px; padding: 4px 12px; font-size: .85rem; font-weight: 700; }
  .redeem-btn { background: #6c63ff; color: #fff; border: none; border-radius: 8px; padding: 7px 16px; font-size: .85rem; font-weight: 600; cursor: pointer; }
  .redeem-btn:disabled { background: #ccc; cursor: not-allowed; }
  .section-title { font-size: 1.2rem; font-weight: 700; color: #1a1a2e; margin-bottom: 16px; }
</style>
</head>
<body>
<nav>
  <h1>Loyalte</h1>
  <div>
    <a href="dashboard.php">Tổng quan</a>
    <a href="history.php">Lịch sử</a>
    <a href="logout.php">Đăng xuất</a>
  </div>
</nav>

<div class="container">
  <div class="pts-bar">
    <span>Điểm của bạn</span>
    <strong><?= number_format((int)$customer['points']) ?></strong>
  </div>

  <?php if ($message): ?>
  <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <h2 class="section-title">Danh sách ưu đãi</h2>

  <div class="reward-grid">
  <?php foreach ($rewards as $r):
    $canRedeem  = (int)$customer['points'] >= (int)$r['points_required'];
    $catLabel   = $catLabels[$r['category']] ?? '🎁 Khác';
  ?>
    <div class="reward-card">
      <div class="reward-cat"><?= $catLabel ?></div>
      <div class="reward-name"><?= htmlspecialchars($r['name']) ?></div>
      <div class="reward-desc"><?= htmlspecialchars($r['description']) ?></div>
      <div class="reward-pts">
        <span class="pts-badge"><?= number_format((int)$r['points_required']) ?> điểm</span>
        <?php if ($canRedeem): ?>
        <form method="POST" onsubmit="return confirm('Đổi <?= htmlspecialchars(addslashes($r['name'])) ?> với <?= number_format((int)$r['points_required']) ?> điểm?')">
          <input type="hidden" name="reward_id" value="<?= htmlspecialchars($r['id']) ?>">
          <button type="submit" class="redeem-btn">Đổi</button>
        </form>
        <?php else: ?>
        <button class="redeem-btn" disabled>Chưa đủ điểm</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
</body>
</html>
