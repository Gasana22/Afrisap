<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;

/**
 * The one non-admin-created account path in the app: a Farm Owner signs up
 * their own organization directly. Every other tenant user (Farm Manager,
 * Agronomist, Field Worker, ...) is created by that Farm Owner via /team,
 * not through this flow.
 */
class RegistrationController extends Controller
{
    public function showSignup(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('public/auth/signup', ['pageTitle' => 'Create your farm account'], 'auth');
    }

    public function signup(): void
    {
        $validator = (new Validator($_POST))
            ->required('organization_name', 'Farm/organization name')
            ->required('name', 'Your name')
            ->required('email', 'Email')
            ->email('email')
            ->required('password', 'Password')
            ->minLength('password', 8, 'Password');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/signup');
        }

        $email = strtolower(trim((string) $this->input('email')));
        $password = (string) $this->input('password');

        if ($password !== (string) $this->input('password_confirm')) {
            $this->flash('danger', 'Passwords do not match.');
            $this->redirect('/signup');
        }

        if (User::findByEmail($email)) {
            $this->flash('danger', 'An account with that email already exists.');
            $this->redirect('/signup');
        }

        $farmOwnerRole = Role::findBySlug('farm_owner');

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $organizationId = Organization::create((string) $this->input('organization_name'));
            $userId = User::create([
                'name' => $this->input('name'),
                'email' => $email,
                'phone' => $this->input('phone'),
                'password' => $password,
                'role_id' => $farmOwnerRole['id'],
                'organization_id' => $organizationId,
                'status' => 'active',
                'mfa_enabled' => 1,
            ]);
            Organization::setOwner($organizationId, $userId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $result = Auth::attemptLogin($email, $password, 'tenant');
        AuditLogger::log('signup', 'organizations', (string) $organizationId, null, [
            'organization_name' => $this->input('organization_name'),
            'owner_user_id' => $userId,
        ], $userId, $organizationId);

        if (!empty($result['mfa_required'])) {
            $this->redirect('/mfa');
        }

        $this->flash('success', 'Welcome to Afrisap SFMTP! Your farm account is ready.');
        $this->redirect('/');
    }
}
