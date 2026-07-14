<?php
declare(strict_types=1);

// ============================================================
// HTML HELPERS
// ============================================================

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path): string
{
    // Ensure BASE_PATH is defined
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', '');
    }
    
    // Normalize BASE_PATH - remove trailing slash
    $basePath = rtrim(BASE_PATH, '/');
    
    // Normalize path - ensure it has a leading slash
    $path = '/' . ltrim($path, '/');
    
    // Build URL - if base path is empty, just return the path
    if ($basePath === '') {
        return $path;
    }
    
    return $basePath . $path;
}

function assetUrl(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function uploadUrl(string $path): string
{
    return url('uploads/' . ltrim($path, '/'));
}

// ============================================================
// CSRF PROTECTION
// ============================================================

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token. Go back and try again.');
    }
}

// ============================================================
// FLASH MESSAGES
// ============================================================

function flash_set(string $type, string $message): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function flash_display(): void
{
    $flashes = flash_get();
    foreach ($flashes as $flash) {
        $type = h($flash['type']);
        $message = h($flash['message']);
        echo '<div class="flash flash-' . $type . '">' . $message . '</div>';
    }
}

// ============================================================
// REDIRECT
// ============================================================

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function redirectBack(): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
    header('Location: ' . $referer);
    exit;
}

// ============================================================
// DATE HELPERS
// ============================================================

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);

    if ($diff < 60) {
        return 'just now';
    }

    $steps = [
        31536000 => 'y',
        2592000 => 'mo',
        86400 => 'd',
        3600 => 'h',
        60 => 'm',
    ];

    foreach ($steps as $seconds => $label) {
        $count = intdiv($diff, $seconds);
        if ($count >= 1) {
            return $count . $label . ' ago';
        }
    }

    return 'just now';
}

function formatDate(?string $date, string $format = 'F j, Y'): string
{
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

function formatDateInput(?string $date): string
{
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    return date('Y-m-d', $timestamp);
}

// ============================================================
// STRING HELPERS
// ============================================================

function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

function slugify(string $text): string
{
    // Replace non-alphanumeric characters with hyphens
    $text = preg_replace('/[^a-zA-Z0-9]+/', '-', $text);
    
    // Remove leading/trailing hyphens
    $text = trim($text, '-');
    
    // Convert to lowercase
    return strtolower($text);
}

// ============================================================
// FILE HELPERS
// ============================================================

function getFileExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isImage(string $filename): bool
{
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    return in_array(getFileExtension($filename), $extensions);
}

// ============================================================
// SECURITY HELPERS
// ============================================================

function randomString(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}