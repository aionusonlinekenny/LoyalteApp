<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$newHash = password_hash('admin123', PASSWORD_BCRYPT);

$db = get_db();
$db->prepare('UPDATE staff_accounts SET password_hash = ? WHERE email = ?')
   ->execute([$newHash, 'admin@loyalte.app']);

$stmt = $db->prepare('SELECT email, password_hash FROM staff_accounts WHERE email = ?');
$stmt->execute(['admin@loyalte.app']);
$row = $stmt->fetch();

echo json_encode([
    'updated'  => true,
    'email'    => $row['email'],
    'verify'   => password_verify('admin123', $row['password_hash']),
    'new_hash' => $row['password_hash'],
], JSON_PRETTY_PRINT);
