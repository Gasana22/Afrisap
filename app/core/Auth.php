<?php

namespace App\Core;

use PDO;

class Auth
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300;

    public static function attemptLogin(string $email, string $password): array
    {
        $email = trim(strtolower($email));

        if (self::isRateLimited($email)) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Try again in a few minutes.'];
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id WHERE u.email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::registerFailedAttempt($email);
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['ok' => false, 'error' => 'This account is inactive. Contact your administrator.'];
        }

        self::clearFailedAttempts($email);

        if ((int) $user['mfa_enabled'] === 1) {
            $code = self::generateOtp((int) $user['id'], 'login_mfa');
            $_SESSION['mfa_pending_user_id'] = (int) $user['id'];
            Mailer::send(
                $user['email'],
                'Your Afrisap SFMTP verification code',
                "Hello {$user['name']},<br><br>Your one-time verification code is: <b>{$code}</b><br>It expires in 10 minutes."
            );
            return ['ok' => true, 'mfa_required' => true];
        }

        self::completeLogin($user);
        return ['ok' => true, 'mfa_required' => false];
    }

    public static function verifyMfa(string $code): array
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;
        if (!$userId) {
            return ['ok' => false, 'error' => 'Session expired, please log in again.'];
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM otp_codes WHERE user_id = :uid AND purpose = 'login_mfa'
            AND code = :code AND used_at IS NULL AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute(['uid' => $userId, 'code' => $code]);
        $otp = $stmt->fetch();

        if (!$otp) {
            return ['ok' => false, 'error' => 'Invalid or expired code.'];
        }

        $pdo->prepare('UPDATE otp_codes SET used_at = NOW() WHERE id = :id')->execute(['id' => $otp['id']]);

        $userStmt = $pdo->prepare('SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id WHERE u.id = :id');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();

        unset($_SESSION['mfa_pending_user_id']);
        self::completeLogin($user);

        return ['ok' => true];
    }

    private static function completeLogin(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role_slug'] = $user['role_slug'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['permissions'] = self::loadPermissions((int) $user['role_id']);

        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $user['id']]);

        AuditLogger::log('login', 'users', (string) $user['id']);
    }

    public static function logout(): void
    {
        if (self::check()) {
            AuditLogger::log('logout', 'users', (string) $_SESSION['user_id']);
        }
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, u.phone, u.avatar_path, u.role_id, r.slug AS role_slug, r.name AS role_name
            FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $cached = $stmt->fetch() ?: null;
        return $cached;
    }

    public static function hasPermission(string $code): bool
    {
        return self::check() && in_array($code, $_SESSION['permissions'] ?? [], true);
    }

    public static function require(string $permission): void
    {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login');
            exit;
        }
        if (!self::hasPermission($permission)) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    private static function loadPermissions(int $roleId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT p.code FROM role_permissions rp
            JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = :role_id');
        $stmt->execute(['role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function generateOtp(int $userId, string $purpose): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO otp_codes (user_id, code, purpose, expires_at)
            VALUES (:uid, :code, :purpose, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
        $stmt->execute(['uid' => $userId, 'code' => $code, 'purpose' => $purpose]);
        return $code;
    }

    private static function isRateLimited(string $email): bool
    {
        $key = 'login_attempts_' . md5($email);
        $data = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];
        if ($data['count'] >= self::MAX_LOGIN_ATTEMPTS && (time() - $data['first']) < self::LOCKOUT_SECONDS) {
            return true;
        }
        return false;
    }

    private static function registerFailedAttempt(string $email): void
    {
        $key = 'login_attempts_' . md5($email);
        $data = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];
        if ((time() - $data['first']) >= self::LOCKOUT_SECONDS) {
            $data = ['count' => 0, 'first' => time()];
        }
        $data['count']++;
        $_SESSION[$key] = $data;
    }

    private static function clearFailedAttempts(string $email): void
    {
        unset($_SESSION['login_attempts_' . md5($email)]);
    }
}
