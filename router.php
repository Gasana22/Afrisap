<?php
/**
 * Router for PHP's built-in development server, mirroring the .htaccess
 * rewrite rules used in production (Apache). Not used by Apache/Docker.
 *
 * Usage: php -S localhost:8080 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve existing static files (css/js/images/storage) as-is.
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath) && !str_ends_with($filePath, '.php')) {
    return false;
}

// Block direct access to internal folders.
if (preg_match('#^/(config|includes|database|logs|vendor)/#', $uri)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/public/index.php';
    return true;
}

if (preg_match('#^/org/([^/]+)/?([a-zA-Z0-9_-]*)\.?p?h?p?$#', $uri, $m)) {
    $slug = $m[1];
    $page = $m[2] !== '' ? $m[2] : 'index';
    $target = __DIR__ . "/org/_slug/$page.php";
    if (is_file($target)) {
        $_GET['slug'] = $slug;
        require $target;
        return true;
    }
}

if (str_ends_with($uri, '.php') && is_file(__DIR__ . $uri)) {
    require __DIR__ . $uri;
    return true;
}

http_response_code(404);
echo 'Not found';
