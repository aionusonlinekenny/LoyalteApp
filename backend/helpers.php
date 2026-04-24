<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ─── Response helpers ─────────────────────────────────────────────────────────

function json_success(array $data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function json_error(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ─── Auth ─────────────────────────────────────────────────────────────────────

function auth_required(): array {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (strpos($auth, 'Bearer ') !== 0) {
        json_error('Unauthorized', 401);
    }

    $token = substr($auth, 7);
    $db    = get_db();
    $nowMs = (int)(microtime(true) * 1000);

    $stmt = $db->prepare(
        'SELECT s.id, s.email, s.name FROM auth_tokens t
         JOIN staff_accounts s ON s.id = t.staff_id
         WHERE t.token = ? AND t.expires_at > ?'
    );
    $stmt->execute([$token, $nowMs]);
    $staff = $stmt->fetch();

    if (!$staff) {
        json_error('Unauthorized', 401);
    }

    return $staff;
}

// ─── Tier helper ──────────────────────────────────────────────────────────────

function tier_from_points(int $points): string {
    if ($points >= 2500) return 'PLATINUM';
    if ($points >= 1000) return 'GOLD';
    if ($points >= 500)  return 'SILVER';
    return 'BRONZE';
}

// ─── UUID v4 ──────────────────────────────────────────────────────────────────

function uuid4(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ─── CORS headers ─────────────────────────────────────────────────────────────

function set_cors_headers(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    // Allow the website and Android app (no origin header = mobile app)
    $allowed = [CORS_ORIGIN, 'https://stonephovaldosta.com'];
    if (!$origin || in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . ($origin ?: CORS_ORIGIN));
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
}

// ─── Request body ─────────────────────────────────────────────────────────────

function json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
