<?php
/**
 * Application bootstrap - included at the top of every entry-point script.
 */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/functions.php';

$config = app_config();

date_default_timezone_set($config['app']['timezone']);

error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', LOGS_PATH . '/errors.log');

set_exception_handler(function (Throwable $e): void {
    app_log_error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);

    // These two failure modes mean the app hasn't finished being set up
    // (not a real application bug), so explain them plainly - regardless
    // of debug mode - instead of a blank "something went wrong" that looks
    // identical to every page being broken.
    if ($e instanceof PDOException) {
        render_setup_notice(
            'Can\'t connect to the database',
            'Copy .env.example to .env and fill in DB_HOST/DB_NAME/DB_USER/DB_PASSWORD for your MySQL/MariaDB
             server, create the database, then run:<br><code>php database/migrate.php</code><br><code>php database/seed.php</code>
             (optional, adds demo data). See docs/SETUP.md for the full walkthrough.'
        );

        return;
    }

    if (str_contains($e->getMessage(), 'vendor/autoload.php')) {
        render_setup_notice(
            'Dependencies not installed',
            'Run <code>composer install</code> in the project root, then reload this page.'
        );

        return;
    }

    if (app_config()['app']['debug']) {
        echo '<pre>' . e($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        echo 'Something went wrong. Please try again later.';
    }
});

function render_setup_notice(string $title, string $instructions): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<title>Setup required</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#f5f7f6;color:#1f2937;display:flex;'
        . 'min-height:100vh;align-items:center;justify-content:center;margin:0;padding:1.5rem;}'
        . '.box{max-width:560px;background:#fff;border-radius:14px;padding:2rem;box-shadow:0 1px 4px rgba(0,0,0,.08);}'
        . 'h1{font-size:1.25rem;margin:0 0 .75rem;color:#1a7a4c;} '
        . 'code{background:#f1f2f4;padding:.15em .4em;border-radius:4px;display:inline-block;margin:.15em 0;}'
        . 'p{line-height:1.6;}</style></head><body><div class="box">'
        . '<h1>' . e($title) . '</h1><p>' . $instructions . '</p></div></body></html>';
}

require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/session.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/tenant.php';
require_once ROOT_PATH . '/includes/validation.php';
require_once ROOT_PATH . '/includes/sanitization.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once ROOT_PATH . '/includes/gps.php';
require_once ROOT_PATH . '/includes/charts.php';
require_once ROOT_PATH . '/includes/notifications.php';
require_once ROOT_PATH . '/includes/audit.php';

require_once ROOT_PATH . '/includes/templates/header.php';
require_once ROOT_PATH . '/includes/templates/footer.php';
require_once ROOT_PATH . '/includes/templates/auth-layout.php';
require_once ROOT_PATH . '/includes/templates/navigation.php';
require_once ROOT_PATH . '/includes/templates/sidebar.php';
require_once ROOT_PATH . '/includes/templates/alerts.php';
require_once ROOT_PATH . '/includes/templates/modals.php';
require_once ROOT_PATH . '/includes/templates/cards.php';

session_start_app();
