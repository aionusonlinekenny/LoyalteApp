<?php
require_once __DIR__ . '/config.php';

// Shared helpers (uuid4, tier_from_points)
if (!function_exists('uuid4')) {
    function uuid4(): string {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('tier_from_points')) {
    function tier_from_points(int $points): string {
        if ($points >= 2500) return 'PLATINUM';
        if ($points >= 1000) return 'GOLD';
        if ($points >= 500)  return 'SILVER';
        return 'BRONZE';
    }
}

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_TTL,
            'path'     => '/',
            'secure'   => false,   // set to true on HTTPS / VPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function get_customer(): ?array {
    start_session();
    return $_SESSION['customer'] ?? null;
}

function require_customer(): array {
    $customer = get_customer();
    if (!$customer) {
        header('Location: index.php');
        exit;
    }
    return $customer;
}

function login_customer(array $customer): void {
    start_session();
    session_regenerate_id(true);
    $_SESSION['customer'] = $customer;
}

function logout_customer(): void {
    start_session();
    session_unset();
    session_destroy();
}

// Re-fetch fresh customer data from DB (call on pages that show points)
function refresh_customer(): array {
    $c = require_customer();
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$c['id']]);
    $fresh = $stmt->fetch();
    if (!$fresh) { logout_customer(); header('Location: index.php'); exit; }
    $_SESSION['customer'] = $fresh;
    return $fresh;
}
