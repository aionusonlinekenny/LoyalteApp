<?php
require_once __DIR__ . '/helpers.php';

set_cors_headers();

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse path: strip leading /api/
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = preg_replace('#^/[^/]+/api/#', '', $uri);  // strip basepath + /api/
$uri    = trim($uri, '/');
$parts  = explode('/', $uri);
$resource = $parts[0] ?? '';
$id       = $parts[1] ?? null;
$sub      = $parts[2] ?? null;
$method   = $_SERVER['REQUEST_METHOD'];

switch ($resource) {
    case 'auth':
        require __DIR__ . '/controllers/auth.php';
        break;
    case 'customers':
        require __DIR__ . '/controllers/customers.php';
        break;
    case 'rewards':
        require __DIR__ . '/controllers/rewards.php';
        break;
    case 'transactions':
        require __DIR__ . '/controllers/transactions.php';
        break;
    case 'redemptions':
        require __DIR__ . '/controllers/redemptions.php';
        break;
    default:
        json_error('Not found', 404);
}
