<?php

use App\Core\Csrf;
use App\Core\Router;

require __DIR__ . '/../app/bootstrap.php';

Csrf::requireValid();

$router = new Router();
require __DIR__ . '/../app/config/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
