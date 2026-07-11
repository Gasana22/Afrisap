<?php

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Models\Permission;
use App\Models\Role;

/** The real RBAC editor -- platform-only. Edits the full role catalog (both
 * scopes: a tenant role's permissions still apply to every organization
 * using it, so this stays a Super Admin action, not something any one
 * tenant can touch). */
class RoleController extends AdminController
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
            $this->redirect('/platform/roles');
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
            $this->redirect('/platform/roles');
        }

        $before = Role::permissionCodes($id);
        $permissionIds = array_map('intval', $this->input('permissions', []) ?: []);
        Role::syncPermissions($id, $permissionIds);

        AuditLogger::log('update_permissions', 'roles', (string) $id, ['permission_ids' => $before], ['permission_ids' => $permissionIds]);

        $this->flash('success', "Permissions updated for {$role['name']}.");
        $this->redirect('/platform/roles');
    }
}
