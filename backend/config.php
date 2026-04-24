<?php
// Database connection settings
define('DB_HOST',    'custsql-dom09.eigbox.net');
define('DB_NAME',    'loyalteapp');
define('DB_USER',    'loyaltyapp');
define('DB_PASS',    'Stonepho1525@');
define('DB_CHARSET', 'utf8mb4');

// Token lifetime: 30 days in milliseconds
define('TOKEN_TTL_MS', 30 * 24 * 60 * 60 * 1000);

// Allow requests from the Android app and web
define('CORS_ORIGIN', 'https://www.stonephovaldosta.com');

// Clover POS App credentials
// Set via Admin → Loyalty → Clover Settings, or fill in here directly
define('CLOVER_APP_ID',     getenv('CLOVER_APP_ID')     ?: '');
define('CLOVER_APP_SECRET', getenv('CLOVER_APP_SECRET') ?: '');
