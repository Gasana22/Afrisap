<?php
declare(strict_types=1);

// ============================================================
// DATABASE CONFIGURATION
// ============================================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'safarisap');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');

// ============================================================
// BASE PATH - CRITICAL FOR URL GENERATION
// ============================================================

// Since all your PHP files are in the root directory (htdocs/safarisap/)
// and you access the site at http://localhost/safarisap/
// BASE_PATH should be '/safarisap'. Override with the APP_BASE_PATH env
// var when serving from a different subfolder (or the domain root).
define('BASE_PATH', getenv('APP_BASE_PATH') !== false ? getenv('APP_BASE_PATH') : '/safarisap');

// ============================================================
// MAIL CONFIGURATION
// ============================================================

define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@safarisap.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Safarisap');
define('MAIL_ADMIN_ADDRESS', getenv('MAIL_ADMIN_ADDRESS') ?: 'info@safarisap.com');

// ============================================================
// SITE CONFIGURATION
// ============================================================

define('SITE_NAME', 'Safarisap');
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_PATH);
define('SITE_TIMEZONE', 'Africa/Kampala');

// Set timezone
date_default_timezone_set(SITE_TIMEZONE);

// ============================================================
// ERROR REPORTING (Disable in production)
// ============================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);