<?php
/**
 * Auth functions. All session-based; nothing here is a class on purpose,
 * to match the flat-file style of the rest of the app.
 */

const MAX_LOGIN_ATTEMPTS = 5;
const ATTEMPT_WINDOW_MINUTES = 15;

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    return $cached = $stmt->fetch() ?: null;
}

function is_platform_user(): bool
{
    return ($_SESSION['role_scope'] ?? null) === 'platform';
}

function current_organization_id(): ?int
{
    return $_SESSION['organization_id'] ?? null;
}

function has_permission(string $code): bool
{
    if (!is_logged_in()) {
        return false;
    }
    static $cache = [];
    $roleId = $_SESSION['role_id'];
    if (isset($cache[$roleId][$code])) {
        return $cache[$roleId][$code];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM role_permissions rp
         JOIN permissions p ON p.id = rp.permission_id
         WHERE rp.role_id = :role_id AND p.code = :code'
    );
    $stmt->execute(['role_id' => $roleId, 'code' => $code]);

    return $cache[$roleId][$code] = ((int) $stmt->fetchColumn()) > 0;
}

/**
 * $requiredScope, when set ('platform' or 'tenant'), rejects a correct
 * password if the account belongs to the other portal -- the Admin Portal
 * (Super Admin / Manager / Accountant, who keep the platform operational)
 * and the Farm Portal (Farm Owner and their own tenant staff, who manage
 * their own farms) are separate login doors into the same admin/ panel.
 *
 * @return array{ok: bool, error?: string, mfa_required?: bool}
 */
function attempt_login(string $email, string $password, string $ip, ?string $requiredScope = null): array
{
    if (is_rate_limited($email, $ip)) {
        return ['ok' => false, 'error' => 'Too many attempts. Try again in a few minutes.'];
    }

    $stmt = db()->prepare(
        'SELECT u.*, r.slug AS role_slug, r.scope AS role_scope
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.email = :email AND u.status = "active"
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    log_login_attempt($email, $ip);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }

    if ($requiredScope !== null && $user['role_scope'] !== $requiredScope) {
        $otherPortal = $requiredScope === 'platform' ? 'Farm Portal' : 'Admin Portal';
        return ['ok' => false, 'error' => "This account isn't for this portal. Try the $otherPortal login instead."];
    }

    if ((bool) $user['mfa_enabled']) {
        $_SESSION['mfa_pending_user_id'] = $user['id'];
        generate_and_send_otp((int) $user['id']);
        return ['ok' => true, 'mfa_required' => true];
    }

    log_user_in($user);
    return ['ok' => true, 'mfa_required' => false];
}

function log_user_in(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']         = $user['id'];
    $_SESSION['role_id']         = $user['role_id'];
    $_SESSION['role_slug']       = $user['role_slug'];
    $_SESSION['role_scope']      = $user['role_scope']; // 'platform' | 'tenant'
    $_SESSION['organization_id'] = $user['organization_id'];
    $_SESSION['name']            = $user['name'];
    unset($_SESSION['mfa_pending_user_id']);

    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')
        ->execute(['id' => $user['id']]);
}

function log_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function is_rate_limited(string $email, string $ip): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE (email = :email OR ip_address = :ip)
         AND created_at > (NOW() - INTERVAL :minutes MINUTE)'
    );
    $stmt->bindValue('email', $email);
    $stmt->bindValue('ip', $ip);
    $stmt->bindValue('minutes', ATTEMPT_WINDOW_MINUTES, PDO::PARAM_INT);
    $stmt->execute();

    return ((int) $stmt->fetchColumn()) >= MAX_LOGIN_ATTEMPTS;
}

function log_login_attempt(string $email, string $ip): void
{
    db()->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)')
        ->execute(['email' => $email, 'ip' => $ip]);
}

const OTP_LENGTH = 6;
const OTP_TTL_MINUTES = 10;

/**
 * Generates a fresh one-time code, stores it in otp_codes, and delivers it.
 * Returns the code only so a dev-mode UI can display it -- never log or
 * display this in a real deployment once a real delivery channel exists.
 */
function generate_and_send_otp(int $userId, string $purpose = 'login_mfa'): string
{
    $code = str_pad((string) random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);

    db()->prepare(
        'INSERT INTO otp_codes (user_id, code, purpose, expires_at) VALUES (:user_id, :code, :purpose, :expires_at)'
    )->execute([
        'user_id' => $userId,
        'code' => $code,
        'purpose' => $purpose,
        'expires_at' => date('Y-m-d H:i:s', time() + OTP_TTL_MINUTES * 60),
    ]);

    deliver_otp($userId, $code);

    return $code;
}

/**
 * Stand-in for real SMS/email delivery -- appends to storage/logs/otp.log.
 * Swap this for an actual provider (SMS gateway / SMTP) when one is chosen;
 * nothing else in the login flow needs to change when that happens.
 */
function deliver_otp(int $userId, string $code): void
{
    $line = sprintf("[%s] OTP for user #%d: %s (expires in %d min)\n", date('Y-m-d H:i:s'), $userId, $code, OTP_TTL_MINUTES);
    @file_put_contents(__DIR__ . '/../storage/logs/otp.log', $line, FILE_APPEND);
}
