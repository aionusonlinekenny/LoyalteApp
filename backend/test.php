<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email    = 'admin@loyalte.app';
$password = 'admin123';
$db       = get_db();

// Step 1: find staff
$stmt = $db->prepare('SELECT id, password_hash, name FROM staff_accounts WHERE email = ?');
$stmt->execute([$email]);
$staff = $stmt->fetch();

$results = [
    'step1_staff_found'   => (bool)$staff,
    'step2_password_ok'   => $staff ? password_verify($password, $staff['password_hash']) : false,
    'step3_token_created' => false,
    'step4_full_response' => null,
    'error'               => null,
];

if (!$staff || !$results['step2_password_ok']) {
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// Step 3: create token
try {
    $token     = bin2hex(random_bytes(32));
    $nowMs     = (int)(microtime(true) * 1000);
    $expiresAt = $nowMs + TOKEN_TTL_MS;

    $db->prepare('INSERT INTO auth_tokens (token, staff_id, expires_at) VALUES (?, ?, ?)')
       ->execute([$token, $staff['id'], $expiresAt]);

    $results['step3_token_created'] = true;
    $results['step4_full_response'] = [
        'success'    => true,
        'token'      => substr($token, 0, 8) . '...',
        'expires_at' => $expiresAt,
        'staff'      => ['id' => $staff['id'], 'name' => $staff['name'], 'email' => $email],
    ];
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
