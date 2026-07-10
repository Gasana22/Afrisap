<?php
declare(strict_types=1);

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'safarisap');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Base path the app is served from, e.g. '/afrisap' when running under
// http://localhost/afrisap/. Leave empty when served from the domain root.
define('BASE_PATH', getenv('APP_BASE_PATH') ?: '');
