<?php

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    public function index(): void
    {
        $this->view('admin/roles/index', ['pageTitle' => 'Roles & Permissions', 'roles' => Role::all()]);
    }

    public function edit(array $params): void
    {
        $role = Role::find((int) $params['id']);
        if (!$role) {
            $this->flash('danger', 'Role not found.');
            $this->redirect('/admin/roles');
        }

        $this->view('admin/roles/edit', [
            'pageTitle' => 'Edit Role: ' . $role['name'],
            'role' => $role,
            'permissionGroups' => Permission::allGroupedByModule(),
            'assignedPermissionIds' => Role::permissionCodes((int) $role['id']),
        ]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $role = Role::find($id);
        if (!$role) {
            $this->flash('danger', 'Role not found.');
            $this->redirect('/admin/roles');
        }

        $before = Role::permissionCodes($id);
        $permissionIds = array_map('intval', $this->input('permissions', []) ?: []);
        Role::syncPermissions($id, $permissionIds);

        AuditLogger::log('update_permissions', 'roles', (string) $id, ['permission_ids' => $before], ['permission_ids' => $permissionIds]);

        $this->flash('success', "Permissions updated for {$role['name']}.");
        $this->redirect('/admin/roles');
    }
}
