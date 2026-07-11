<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Role;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        $this->view('admin/users/index', ['pageTitle' => 'Users', 'users' => User::all()]);
    }

    public function create(): void
    {
        $this->view('admin/users/create', ['pageTitle' => 'Add User', 'roles' => Role::all()]);
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
            $this->redirect('/admin/users/create');
        }

        if (User::findByEmail(strtolower(trim((string) $this->input('email'))))) {
            $this->flash('danger', 'A user with that email already exists.');
            $this->redirect('/admin/users/create');
        }

        $id = User::create([
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'password' => $this->input('password'),
            'role_id' => (int) $this->input('role_id'),
            'status' => $this->input('status', 'active'),
            'mfa_enabled' => $this->input('mfa_enabled') ? 1 : 0,
        ]);

        AuditLogger::log('create', 'users', (string) $id, null, User::find($id));

        $this->flash('success', 'User created.');
        $this->redirect('/admin/users');
    }

    public function edit(array $params): void
    {
        $user = User::find((int) $params['id']);
        if (!$user) {
            $this->flash('danger', 'User not found.');
            $this->redirect('/admin/users');
        }

        $this->view('admin/users/edit', ['pageTitle' => 'Edit User', 'editUser' => $user, 'roles' => Role::all()]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = User::find($id);
        if (!$before) {
            $this->flash('danger', 'User not found.');
            $this->redirect('/admin/users');
        }

        $validator = (new Validator($_POST))
            ->required('name', 'Name')
            ->required('email', 'Email')
            ->email('email')
            ->required('role_id', 'Role');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/admin/users/{$id}/edit");
        }

        User::update($id, [
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'role_id' => (int) $this->input('role_id'),
            'status' => $this->input('status', 'active'),
            'mfa_enabled' => $this->input('mfa_enabled') ? 1 : 0,
            'password' => $this->input('password', ''),
        ]);

        AuditLogger::log('update', 'users', (string) $id, $before, User::find($id));

        $this->flash('success', 'User updated.');
        $this->redirect('/admin/users');
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];

        if ($id === Auth::id()) {
            $this->flash('danger', 'You cannot deactivate your own account.');
            $this->redirect('/admin/users');
        }

        $before = User::find($id);
        $newStatus = $before && $before['status'] === 'active' ? 'inactive' : 'active';
        User::setStatus($id, $newStatus);

        AuditLogger::log($newStatus === 'active' ? 'activate' : 'deactivate', 'users', (string) $id, $before, ['status' => $newStatus]);

        $this->flash('success', 'User status updated.');
        $this->redirect('/admin/users');
    }
}
