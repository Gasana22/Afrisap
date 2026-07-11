<?php

namespace App\Controllers\Platform;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Validator;

/**
 * The platform portal's own login -- deliberately separate from the tenant
 * app's AuthController/routes. A platform credential does not work at
 * /login, and a tenant credential does not work here (Auth::attemptLogin's
 * $expectedScope rejects both with the same generic message either way).
 */
class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::isPlatform()) {
            $this->redirect('/platform');
        }
        $this->view('platform/auth/login', ['pageTitle' => 'Platform Admin Login'], 'platform_auth');
    }

    public function login(): void
    {
        $email = trim((string) $this->input('email', ''));
        $password = (string) $this->input('password', '');

        $validator = (new Validator($_POST))->required('email', 'Email')->required('password', 'Password');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/platform/login');
        }

        $result = Auth::attemptLogin($email, $password, 'platform');

        if (!$result['ok']) {
            $this->flash('danger', $result['error']);
            $this->redirect('/platform/login');
        }

        if (!empty($result['mfa_required'])) {
            $this->redirect('/platform/mfa');
        }

        $target = $_SESSION['redirect_after_login'] ?? '/platform';
        unset($_SESSION['redirect_after_login']);
        $this->redirect($target);
    }

    public function showMfa(): void
    {
        if (empty($_SESSION['mfa_pending_user_id'])) {
            $this->redirect('/platform/login');
        }
        $this->view('platform/auth/mfa', ['pageTitle' => 'Verify identity'], 'platform_auth');
    }

    public function verifyMfa(): void
    {
        $code = trim((string) $this->input('code', ''));
        $result = Auth::verifyMfa($code);

        if (!$result['ok']) {
            $this->flash('danger', $result['error']);
            $this->redirect('/platform/mfa');
        }

        $target = $_SESSION['redirect_after_login'] ?? '/platform';
        unset($_SESSION['redirect_after_login']);
        $this->redirect($target);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/platform/login');
    }
}
