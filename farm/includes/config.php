<?php
/**
 * Site configuration.
 * Edit the values below for your environment (local XAMPP vs live server).
 */

// If you move this app to a different subfolder or to a domain root,
// update BASE_URL to match. It's used by redirect() and in links.
define('BASE_URL', '/farm');

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'afrisap');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- App ---
define('APP_NAME', 'Afrisap Farm Management');
define('APP_DEBUG', true); // set to false in production

date_default_timezone_set('Africa/Kigali');

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');

session_name('afrisap_session');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 7200,
        'path' => BASE_URL . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // enable once served over HTTPS
    ]);
    session_start();
}
