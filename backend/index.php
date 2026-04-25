<?php
require_once __DIR__ . '/helpers.php';

set_cors_headers();

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse path: find /api/ anywhere in the URI and take everything after it
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$apiPos = strpos($uri, '/api/');
if ($apiPos === false) {
    json_error('Not found', 404);
}
$uri      = trim(substr($uri, $apiPos + 5), '/'); // skip past '/api/'
$parts    = explode('/', $uri);
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
    case 'receipt_codes':
        require __DIR__ . '/controllers/receipt_codes.php';
        break;
    case 'clover':
        require __DIR__ . '/controllers/clover.php';
        break;
    case 'kiosk':
        require __DIR__ . '/controllers/kiosk.php';
        break;
    default:
        json_error('Not found', 404);
}
