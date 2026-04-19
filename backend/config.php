<?php
// Database connection settings
// Change these to match your XAMPP / VPS MySQL credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'loyalteapp');
define('DB_USER', 'root');
define('DB_PASS', '');       // XAMPP default: empty. Change on VPS!
define('DB_CHARSET', 'utf8mb4');

// Token lifetime: 30 days in milliseconds
define('TOKEN_TTL_MS', 30 * 24 * 60 * 60 * 1000);

// Allow requests from the Android app and local web
// On production VPS replace '*' with your actual domain
define('CORS_ORIGIN', '*');
