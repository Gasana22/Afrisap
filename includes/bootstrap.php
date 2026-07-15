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

    if (app_config()['app']['debug']) {
        http_response_code(500);
        echo '<pre>' . e($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo 'Something went wrong. Please try again later.';
    }
});

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
require_once ROOT_PATH . '/includes/templates/navigation.php';
require_once ROOT_PATH . '/includes/templates/sidebar.php';
require_once ROOT_PATH . '/includes/templates/alerts.php';
require_once ROOT_PATH . '/includes/templates/modals.php';
require_once ROOT_PATH . '/includes/templates/cards.php';

session_start_app();
