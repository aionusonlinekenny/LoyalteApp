<?php
require_once __DIR__ . '/auth.php';
$customer = refresh_customer();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$db    = get_db();
$total = (int)$db->prepare('SELECT COUNT(*) FROM loyalty_transactions WHERE customer_id = ?')
              ->execute([$customer['id']]) ? $db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;

$stmtCount = $db->prepare('SELECT COUNT(*) FROM loyalty_transactions WHERE customer_id = ?');
$stmtCount->execute([$customer['id']]);
$total = (int)$stmtCount->fetchColumn();
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare('SELECT * FROM loyalty_transactions WHERE customer_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
$stmt->execute([$customer['id'], $limit, $offset]);
$txns = $stmt->fetchAll();

function fmt_date(int $ms): string { return date('d/m/Y H:i', intdiv($ms, 1000)); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loyalte – Lịch sử điểm</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6fb; min-height: 100vh; }
  nav { background: #1a1a2e; color: #fff; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
  nav h1 { font-size: 1.4rem; font-weight: 800; }
  nav a { color: #ccc; text-decoration: none; font-size: .9rem; margin-left: 18px; }
  nav a:hover { color: #fff; }
  .container { max-width: 680px; margin: 30px auto; padding: 0 16px; }
  .page-title { font-size: 1.3rem; font-weight: 700; color: #1a1a2e; margin-bottom: 18px; }
  .pts-summary { background: #6c63ff; color: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; gap: 24px; }
  .pts-summary div { font-size: .85rem; opacity: .85; }
  .pts-summary strong { display: block; font-size: 1.4rem; font-weight: 800; opacity: 1; }
  .card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06); overflow: hidden; }
  .txn { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid #f0f0f0; }
  .txn:last-child { border-bottom: none; }
  .txn-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-right: 12px; flex-shrink: 0; }
  .txn-left { display: flex; align-items: center; flex: 1; }
  .txn-info p { font-size: .9rem; color: #333; font-weight: 500; }
  .txn-info span { font-size: .78rem; color: #999; }
  .txn-pts { font-weight: 700; font-size: 1rem; flex-shrink: 0; }
  .earned   { color: #27ae60; }
  .redeemed { color: #e74c3c; }
  .adjusted { color: #2980b9; }
  .icon-earned   { background: #e8faf0; }
  .icon-redeemed { background: #fdecea; }
  .icon-adjusted { background: #e8f4fd; }
  .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 20px; }
  .pagination a, .pagination span { padding: 8px 14px; border-radius: 8px; background: #fff; text-decoration: none; color: #6c63ff; font-size: .9rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .pagination .active { background: #6c63ff; color: #fff; }
  .empty { text-align: center; padding: 40px; color: #999; }
</style>
</head>
<body>
<nav>
  <h1>Loyalte</h1>
  <div>
    <a href="dashboard.php">Tổng quan</a>
    <a href="rewards.php">Ưu đãi</a>
    <a href="logout.php">Đăng xuất</a>
  </div>
</nav>

<div class="container">
  <h2 class="page-title">Lịch sử điểm</h2>

  <div class="pts-summary">
    <div><strong><?= number_format((int)$customer['points']) ?></strong>Điểm hiện tại</div>
    <div><strong><?= number_format($total) ?></strong>Tổng giao dịch</div>
  </div>

  <div class="card">
    <?php if (empty($txns)): ?>
    <p class="empty">Chưa có giao dịch nào.</p>
    <?php else: foreach ($txns as $t):
      $pts  = (int)$t['points'];
      $type = strtolower($t['type']);
      $sign = $pts > 0 ? '+' : '';
      $icons = ['earned' => '⭐', 'redeemed' => '🎁', 'adjusted' => '✏️'];
      $icon  = $icons[$type] ?? '•';
    ?>
    <div class="txn">
      <div class="txn-left">
        <div class="txn-icon icon-<?= $type ?>"><?= $icon ?></div>
        <div class="txn-info">
          <p><?= htmlspecialchars($t['description']) ?></p>
          <span><?= fmt_date((int)$t['created_at']) ?></span>
        </div>
      </div>
      <div class="txn-pts <?= $type ?>"><?= $sign . number_format($pts) ?></div>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <?php if ($p === $page): ?>
        <span class="active"><?= $p ?></span>
      <?php else: ?>
        <a href="?page=<?= $p ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
