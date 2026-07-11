<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Session;

$config = require __DIR__ . '/config/config.php';

date_default_timezone_set($config['app']['timezone']);

if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

Session::start();

return $config;
