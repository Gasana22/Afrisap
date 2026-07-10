<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_admin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_login(): void
{
    if (!current_admin()) {
        redirect('/admin/login.php');
    }
}

function require_super_admin(): void
{
    require_login();
    if ((current_admin()['role'] ?? '') !== 'super_admin') {
        http_response_code(403);
        exit('Only super admins can access this page.');
    }
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['admin'] = $user;

    db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

    return true;
}

function log_out(): void
{
    $_SESSION = [];
    session_destroy();
}
