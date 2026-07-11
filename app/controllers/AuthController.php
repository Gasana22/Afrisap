<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Validator;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', ['pageTitle' => 'Login'], 'auth');
    }

    public function login(): void
    {
        $email = trim((string) $this->input('email', ''));
        $password = (string) $this->input('password', '');

        $validator = (new Validator($_POST))->required('email', 'Email')->required('password', 'Password');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/login');
        }

        $result = Auth::attemptLogin($email, $password);

        if (!$result['ok']) {
            $this->flash('danger', $result['error']);
            $this->redirect('/login');
        }

        if (!empty($result['mfa_required'])) {
            $this->redirect('/mfa');
        }

        $target = $_SESSION['redirect_after_login'] ?? '/';
        unset($_SESSION['redirect_after_login']);
        $this->redirect($target);
    }

    public function showMfa(): void
    {
        if (empty($_SESSION['mfa_pending_user_id'])) {
            $this->redirect('/login');
        }
        $this->view('auth/mfa', ['pageTitle' => 'Verify identity'], 'auth');
    }

    public function verifyMfa(): void
    {
        $code = trim((string) $this->input('code', ''));
        $result = Auth::verifyMfa($code);

        if (!$result['ok']) {
            $this->flash('danger', $result['error']);
            $this->redirect('/mfa');
        }

        $target = $_SESSION['redirect_after_login'] ?? '/';
        unset($_SESSION['redirect_after_login']);
        $this->redirect($target);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot-password', ['pageTitle' => 'Forgot password'], 'auth');
    }

    public function sendResetLink(): void
    {
        $email = strtolower(trim((string) $this->input('email', '')));
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Always show the same message, regardless of whether the account exists.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL 60 MINUTE))')
                ->execute(['uid' => $user['id'], 'token' => $token]);

            $config = require __DIR__ . '/../config/config.php';
            $link = rtrim($config['app']['url'], '/') . '/reset-password?token=' . $token;
            Mailer::send($email, 'Reset your Afrisap SFMTP password', "Hello {$user['name']},<br><br>Click to reset your password: <a href=\"{$link}\">{$link}</a><br>This link expires in 60 minutes.");
        }

        $this->flash('success', 'If that email is registered, a reset link has been sent.');
        $this->redirect('/login');
    }

    public function showResetPassword(): void
    {
        $token = (string) $this->input('token', '');
        $this->view('auth/reset-password', ['pageTitle' => 'Reset password', 'token' => $token], 'auth');
    }

    public function resetPassword(): void
    {
        $token = (string) $this->input('token', '');
        $password = (string) $this->input('password', '');

        $validator = (new Validator($_POST))->minLength('password', 8, 'Password');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/reset-password?token=' . urlencode($token));
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = :token AND used_at IS NULL AND expires_at >= NOW()');
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $this->flash('danger', 'This reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
        }

        $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
            ->execute(['hash' => password_hash($password, PASSWORD_BCRYPT), 'id' => $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')->execute(['id' => $reset['id']]);

        $this->flash('success', 'Password updated. You can now log in.');
        $this->redirect('/login');
    }
}
