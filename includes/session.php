<?php
/**
 * Session management.
 */

function session_start_app(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $authConfig = require __DIR__ . '/../config/auth.php';

    session_name($authConfig['session_name']);
    session_set_cookie_params($authConfig['cookie']);
    session_start();

    $timeoutMinutes = (require __DIR__ . '/../config/config.php')['session']['timeout_minutes'];
    $timeoutSeconds = $timeoutMinutes * 60;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
        session_unset();
        session_destroy();
        session_start();
    }

    $_SESSION['last_activity'] = time();

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function session_flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;

        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $value;
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
