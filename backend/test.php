<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$db = get_db();

// 1. Check staff_accounts table exists and has data
$staff = $db->query('SELECT id, email, name, password_hash FROM staff_accounts LIMIT 5')->fetchAll();

// 2. Test password verification
$testEmail    = 'admin@loyalte.app';
$testPassword = 'admin123';

$stmt = $db->prepare('SELECT password_hash FROM staff_accounts WHERE email = ?');
$stmt->execute([$testEmail]);
$row = $stmt->fetch();

$hashMatch = $row ? password_verify($testPassword, $row['password_hash']) : false;

// 3. Check auth_tokens table exists
try {
    $db->query('SELECT COUNT(*) FROM auth_tokens');
    $tokensTableOk = true;
} catch (Exception $e) {
    $tokensTableOk = false;
}

echo json_encode([
    'staff_accounts_found' => count($staff),
    'staff_rows'           => array_map(fn($s) => ['id'=>$s['id'],'email'=>$s['email'],'name'=>$s['name']], $staff),
    'password_verify_admin123' => $hashMatch,
    'auth_tokens_table_ok' => $tokensTableOk,
], JSON_PRETTY_PRINT);
