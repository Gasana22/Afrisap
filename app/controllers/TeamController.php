<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Role;
use App\Models\User;

/**
 * Farm Owner's own staff roster -- scoped entirely to Auth::organizationId().
 * This is the tenant-side replacement for the old /admin/users: a Farm Owner
 * invites Farm Managers, Agronomists, Field Workers etc. into their own
 * organization only, picking from a fixed tenant-role catalog (they can't
 * grant farm_owner or any platform role).
 */
class TeamController extends Controller
{
    public function index(): void
    {
        $this->view('team/index', [
            'pageTitle' => 'Team',
            'members' => User::forOrganization(Auth::organizationId()),
        ]);
    }

    public function create(): void
    {
        $this->view('team/create', [
            'pageTitle' => 'Invite Team Member',
            'roles' => Role::assignableByFarmOwner(),
        ]);
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
            $this->redirect('/team/create');
        }

        if (User::findByEmail(strtolower(trim((string) $this->input('email'))))) {
            $this->flash('danger', 'A user with that email already exists.');
            $this->redirect('/team/create');
        }

        $roleId = (int) $this->input('role_id');
        if (!$this->isAssignableRole($roleId)) {
            $this->flash('danger', 'That role cannot be assigned here.');
            $this->redirect('/team/create');
        }

        $id = User::create([
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'password' => $this->input('password'),
            'role_id' => $roleId,
            'organization_id' => Auth::organizationId(),
            'status' => 'active',
            'mfa_enabled' => $this->input('mfa_enabled') ? 1 : 0,
        ]);

        AuditLogger::log('create', 'users', (string) $id, null, User::findInOrganization($id, Auth::organizationId()));

        $this->flash('success', 'Team member added.');
        $this->redirect('/team');
    }

    public function edit(array $params): void
    {
        $member = User::findInOrganization((int) $params['id'], Auth::organizationId());
        if (!$member) {
            $this->flash('danger', 'Team member not found.');
            $this->redirect('/team');
        }

        $this->view('team/edit', [
            'pageTitle' => 'Edit Team Member',
            'member' => $member,
            'roles' => Role::assignableByFarmOwner(),
        ]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = User::findInOrganization($id, Auth::organizationId());
        if (!$before) {
            $this->flash('danger', 'Team member not found.');
            $this->redirect('/team');
        }

        $validator = (new Validator($_POST))
            ->required('name', 'Name')
            ->required('email', 'Email')
            ->email('email')
            ->required('role_id', 'Role');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/team/{$id}/edit");
        }

        $roleId = (int) $this->input('role_id');
        if (!$this->isAssignableRole($roleId)) {
            $this->flash('danger', 'That role cannot be assigned here.');
            $this->redirect("/team/{$id}/edit");
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

        AuditLogger::log('update', 'users', (string) $id, $before, User::findInOrganization($id, Auth::organizationId()));

        $this->flash('success', 'Team member updated.');
        $this->redirect('/team');
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];

        if ($id === Auth::id()) {
            $this->flash('danger', 'You cannot deactivate your own account.');
            $this->redirect('/team');
        }

        $before = User::findInOrganization($id, Auth::organizationId());
        if (!$before) {
            $this->flash('danger', 'Team member not found.');
            $this->redirect('/team');
        }

        $newStatus = $before['status'] === 'active' ? 'inactive' : 'active';
        User::setStatus($id, $newStatus);

        AuditLogger::log($newStatus === 'active' ? 'activate' : 'deactivate', 'users', (string) $id, $before, ['status' => $newStatus]);

        $this->flash('success', 'Team member status updated.');
        $this->redirect('/team');
    }

    private function isAssignableRole(int $roleId): bool
    {
        foreach (Role::assignableByFarmOwner() as $role) {
            if ((int) $role['id'] === $roleId) {
                return true;
            }
        }
        return false;
    }
}
