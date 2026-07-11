<?php

namespace App\Controllers\Platform;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Validator;
use App\Models\Role;
use App\Models\User;

/**
 * Platform staff only -- Super Admin, Platform Manager, Platform Accountant.
 * Every user created/edited here is organization_id NULL and role-restricted
 * to Role::byScope('platform'), the platform-side mirror of how
 * TeamController restricts a Farm Owner to tenant roles only.
 */
class UserController extends PlatformController
{
    public function index(): void
    {
        $stmt = \App\Core\Database::connection()->query('SELECT u.*, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id WHERE u.organization_id IS NULL ORDER BY u.created_at DESC');
        $this->view('platform/users/index', ['pageTitle' => 'Platform Staff', 'users' => $stmt->fetchAll()]);
    }

    public function create(): void
    {
        $this->view('platform/users/create', ['pageTitle' => 'Add Platform Staff', 'roles' => Role::byScope('platform')]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('name', 'Name')
            ->required('email', 'Email')
            ->email('email')
            ->required('role_id', 'Role')
            ->required('password', 'Temporary password')
            ->minLength('password', 8, 'Temporary password');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/platform/users/create');
        }

        if (User::findByEmail(strtolower(trim((string) $this->input('email'))))) {
            $this->flash('danger', 'A user with that email already exists.');
            $this->redirect('/platform/users/create');
        }

        $roleId = (int) $this->input('role_id');
        if (!$this->isPlatformRole($roleId)) {
            $this->flash('danger', 'That role cannot be assigned here.');
            $this->redirect('/platform/users/create');
        }

        $id = User::create([
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'password' => $this->input('password'),
            'role_id' => $roleId,
            'organization_id' => null,
            'status' => $this->input('status', 'active'),
            'mfa_enabled' => $this->input('mfa_enabled') ? 1 : 0,
        ]);

        AuditLogger::log('create', 'users', (string) $id, null, User::find($id));

        $this->flash('success', 'Platform staff member added.');
        $this->redirect('/platform/users');
    }

    public function edit(array $params): void
    {
        $user = $this->requirePlatformUser((int) $params['id']);

        $this->view('platform/users/edit', ['pageTitle' => 'Edit ' . $user['name'], 'editUser' => $user, 'roles' => Role::byScope('platform')]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = $this->requirePlatformUser($id);

        $validator = (new Validator($_POST))
            ->required('name', 'Name')
            ->required('email', 'Email')
            ->email('email')
            ->required('role_id', 'Role');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/platform/users/{$id}/edit");
        }

        $roleId = (int) $this->input('role_id');
        if (!$this->isPlatformRole($roleId)) {
            $this->flash('danger', 'That role cannot be assigned here.');
            $this->redirect("/platform/users/{$id}/edit");
        }

        User::update($id, [
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'role_id' => $roleId,
            'status' => $this->input('status', 'active'),
            'mfa_enabled' => $this->input('mfa_enabled') ? 1 : 0,
            'password' => $this->input('password', ''),
        ]);

        AuditLogger::log('update', 'users', (string) $id, $before, User::find($id));

        $this->flash('success', 'Platform staff member updated.');
        $this->redirect('/platform/users');
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        if ($id === Auth::id()) {
            $this->flash('danger', 'You cannot deactivate your own account.');
            $this->redirect('/platform/users');
        }

        $before = $this->requirePlatformUser($id);
        $newStatus = $before['status'] === 'active' ? 'inactive' : 'active';
        User::setStatus($id, $newStatus);

        AuditLogger::log($newStatus === 'active' ? 'activate' : 'deactivate', 'users', (string) $id, $before, ['status' => $newStatus]);

        $this->flash('success', 'Status updated.');
        $this->redirect('/platform/users');
    }

    private function requirePlatformUser(int $id): array
    {
        $user = User::find($id);
        if (!$user || $user['organization_id'] !== null) {
            $this->flash('danger', 'User not found.');
            $this->redirect('/platform/users');
        }
        return $user;
    }

    private function isPlatformRole(int $roleId): bool
    {
        foreach (Role::byScope('platform') as $role) {
            if ((int) $role['id'] === $roleId) {
                return true;
            }
        }
        return false;
    }
}
