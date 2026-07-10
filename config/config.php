<?php
declare(strict_types=1);

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'safarisap');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Base path the app is served from, e.g. '/afrisap' when running under
// http://localhost/afrisap/. Leave empty when served from the domain root.
define('BASE_PATH', getenv('APP_BASE_PATH') ?: '');

// Outgoing mail. Uses PHP's built-in mail() -- works out of the box on most
// shared hosting (cPanel, etc.) without extra setup. On a VPS with no local
// MTA configured, mail() will silently fail; see includes/mailer.php.
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@safarisap.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Safarisap');
define('MAIL_ADMIN_ADDRESS', getenv('MAIL_ADMIN_ADDRESS') ?: 'info@safarisap.com');
