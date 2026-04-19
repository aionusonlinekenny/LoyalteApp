<?php
// POST /api/auth/login     { "email": "...", "password": "..." }
// POST /api/auth/logout    (Bearer token required)

$action = $parts[1] ?? '';

if ($method === 'POST' && $action === 'login') {
    $body     = json_body();
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        json_error('Email and password are required');
    }

    $db   = get_db();
    $stmt = $db->prepare('SELECT id, password_hash, name FROM staff_accounts WHERE email = ?');
    $stmt->execute([$email]);
    $staff = $stmt->fetch();

    if (!$staff || !password_verify($password, $staff['password_hash'])) {
        json_error('Invalid email or password', 401);
    }

    // Generate secure token
    $token    = bin2hex(random_bytes(32));
    $nowMs    = (int)(microtime(true) * 1000);
    $expiresAt = $nowMs + TOKEN_TTL_MS;

    $db->prepare('INSERT INTO auth_tokens (token, staff_id, expires_at) VALUES (?, ?, ?)')
       ->execute([$token, $staff['id'], $expiresAt]);

    json_success([
        'token'      => $token,
        'expires_at' => $expiresAt,
        'staff'      => ['id' => $staff['id'], 'name' => $staff['name'], 'email' => $email]
    ]);
}

if ($method === 'POST' && $action === 'logout') {
    auth_required();
    $headers = getallheaders();
    $token   = substr($headers['Authorization'] ?? $headers['authorization'] ?? '', 7);
    get_db()->prepare('DELETE FROM auth_tokens WHERE token = ?')->execute([$token]);
    json_success(['message' => 'Logged out']);
}

json_error('Not found', 404);
