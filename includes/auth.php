<?php
/**
 * Authentication & authorization functions.
 *
 * Three separate "guards" share the same users table but are gated
 * differently:
 *   - platform admin: users.is_admin / is_super_admin
 *   - org-admin:       organization_users membership (role != worker)
 *   - worker:          organization_users membership with role = worker,
 *                       joined to the workers table for profile data
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

function auth_attempt(string $email, string $password): ?array
{
    if (login_rate_limited($email)) {
        return null;
    }

    $user = db_one('SELECT * FROM users WHERE email = :email', ['email' => $email]);

    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
        record_failed_login($email);
        return null;
    }

    clear_login_attempts($email);

    db_query(
        'UPDATE users SET last_login = NOW(), last_ip = :ip WHERE id = :id',
        ['ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'id' => $user['id']]
    );

    return $user;
}

/**
 * Simple DB-backed login throttle, keyed on email: after
 * config/auth.php's max_login_attempts failures within lockout_minutes,
 * further attempts are rejected (as an invalid-credentials response, so
 * lockout state is never revealed to the caller) until the window rolls
 * off or a successful login clears the record.
 */
function login_rate_limited(string $email): bool
{
    $config = require ROOT_PATH . '/config/auth.php';
    $minutes = (int) $config['lockout_minutes'];

    // The cutoff is computed by MySQL's own NOW() rather than PHP's date(),
    // since PHP runs in APP_TIMEZONE (e.g. Africa/Kigali) while created_at
    // is stored in the database's own (UTC) clock - mixing the two would
    // skew the comparison by the timezone offset. $minutes comes from our
    // own config, not user input, so interpolating it is safe.
    $count = (int) db_value(
        "SELECT COUNT(*) FROM login_attempts WHERE email = :email AND created_at > (NOW() - INTERVAL $minutes MINUTE)",
        ['email' => $email]
    );

    return $count >= $config['max_login_attempts'];
}

function record_failed_login(string $email): void
{
    db_insert('login_attempts', [
        'email' => $email,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
}

function clear_login_attempts(string $email): void
{
    db_delete('login_attempts', 'email = :email', ['email' => $email]);
}

function auth_login_platform_admin(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = $user['id'];
}

function auth_login_org_user(array $user, int $organizationId): void
{
    session_regenerate_id(true);
    $_SESSION['org_user_id'] = $user['id'];
    $_SESSION['organization_id'] = $organizationId;
}

function auth_login_worker(array $user, int $organizationId, int $workerId): void
{
    session_regenerate_id(true);
    $_SESSION['worker_user_id'] = $user['id'];
    $_SESSION['worker_organization_id'] = $organizationId;
    $_SESSION['worker_id'] = $workerId;
}

function auth_logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function current_platform_admin(): ?array
{
    if (empty($_SESSION['admin_user_id'])) {
        return null;
    }

    $user = db_one('SELECT * FROM users WHERE id = :id AND is_admin = 1', ['id' => $_SESSION['admin_user_id']]);

    return $user ?: null;
}

function current_org_user(): ?array
{
    if (empty($_SESSION['org_user_id']) || empty($_SESSION['organization_id'])) {
        return null;
    }

    $row = db_one(
        'SELECT u.*, ou.role, ou.organization_id, ou.status AS membership_status
         FROM users u
         JOIN organization_users ou ON ou.user_id = u.id
         WHERE u.id = :user_id AND ou.organization_id = :org_id AND ou.status = "active"',
        ['user_id' => $_SESSION['org_user_id'], 'org_id' => $_SESSION['organization_id']]
    );

    return $row ?: null;
}

function current_worker(): ?array
{
    if (empty($_SESSION['worker_id']) || empty($_SESSION['worker_organization_id'])) {
        return null;
    }

    $row = db_one(
        'SELECT w.*, u.email AS login_email
         FROM workers w
         LEFT JOIN users u ON u.id = w.user_id
         WHERE w.id = :id AND w.organization_id = :org_id AND w.status = "active"',
        ['id' => $_SESSION['worker_id'], 'org_id' => $_SESSION['worker_organization_id']]
    );

    return $row ?: null;
}

function current_organization(): ?array
{
    $orgId = $_SESSION['organization_id'] ?? $_SESSION['worker_organization_id'] ?? null;
    if (!$orgId) {
        return null;
    }

    return db_one('SELECT * FROM organizations WHERE id = :id', ['id' => $orgId]);
}

function require_platform_admin(): array
{
    $admin = current_platform_admin();
    if (!$admin) {
        header('Location: ' . base_url('public/login.php'));
        exit;
    }

    return $admin;
}

function require_org_user(array $allowedRoles = []): array
{
    $user = current_org_user();
    if (!$user) {
        header('Location: ' . base_url('public/login.php'));
        exit;
    }

    if ($allowedRoles && !in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        echo 'Forbidden: your role does not have access to this page.';
        exit;
    }

    return $user;
}

function require_worker(): array
{
    $worker = current_worker();
    if (!$worker) {
        header('Location: ' . base_url('worker/login.php'));
        exit;
    }

    return $worker;
}

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT);
}
