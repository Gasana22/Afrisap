<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::connection();

// Two pools: 'platform' roles run the SaaS itself and belong to no organization;
// 'tenant' roles belong to a Farm Owner's organization and only ever see that
// organization's data. super_admin is the migrated identity of the original
// system_administrator role (see migration 017) -- this INSERT is only reached
// for a fully fresh install where that migration UPDATE matched zero rows.
$roles = [
    ['super_admin', 'Super Admin', 'Full platform administration and oversight', 1, 'platform'],
    ['platform_manager', 'Platform Manager', 'Oversees tenant organizations and platform operations', 1, 'platform'],
    ['platform_accountant', 'Platform Accountant', 'Cross-tenant financial oversight and reporting', 1, 'platform'],
    ['farm_owner', 'Farm Owner', 'Owns the organization\'s farms, manages their own team', 1, 'tenant'],
    ['farm_manager', 'Farm Manager', 'Operates and supervises day-to-day farm activity', 1, 'tenant'],
    ['agronomist', 'Agronomist', 'Crop management specialist', 1, 'tenant'],
    ['livestock_manager', 'Livestock Manager', 'Animal management specialist', 1, 'tenant'],
    ['store_manager', 'Store Manager', 'Inventory and procurement', 1, 'tenant'],
    ['accountant', 'Accountant', 'Finance and reporting', 1, 'tenant'],
    ['field_worker', 'Field Worker', 'Executes daily field/farm tasks', 1, 'tenant'],
    ['supplier', 'Supplier', 'External input supplier, delivers inputs', 1, 'tenant'],
    ['customer', 'Customer', 'External buyer, purchases outputs', 1, 'tenant'],
];

$roleIds = [];
$stmt = $pdo->prepare('INSERT INTO roles (slug, name, description, is_system, scope) VALUES (:slug, :name, :description, :is_system, :scope)
    ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), scope = VALUES(scope)');
foreach ($roles as [$slug, $name, $description, $isSystem, $scope]) {
    $stmt->execute(['slug' => $slug, 'name' => $name, 'description' => $description, 'is_system' => $isSystem, 'scope' => $scope]);
}
foreach ($pdo->query('SELECT id, slug FROM roles') as $row) {
    $roleIds[$row['slug']] = (int) $row['id'];
}

// 'organizations' (platform: manage tenants) and 'team' (tenant: Farm Owner
// manages their own org's staff) replace the old assumption that 'users'/'roles'/
// 'settings'/'audit' were things any admin-ish role could reach -- those four are
// platform-only now (Platform\* controllers), gating the real RBAC editor and
// platform staff management, not farm data.
$modules = ['organizations', 'team', 'users', 'roles', 'settings', 'audit', 'farms', 'crops', 'livestock', 'workers', 'finance', 'procurement', 'inventory', 'assets', 'reports', 'traceability', 'media'];
$actions = ['view', 'create', 'edit', 'delete'];

$permStmt = $pdo->prepare('INSERT INTO permissions (code, module, description) VALUES (:code, :module, :description)
    ON DUPLICATE KEY UPDATE module = VALUES(module)');
foreach ($modules as $module) {
    foreach ($actions as $action) {
        $code = "$module.$action";
        $permStmt->execute([
            'code' => $code,
            'module' => $module,
            'description' => ucfirst($action) . ' ' . $module,
        ]);
    }
}

$permIds = [];
foreach ($pdo->query('SELECT id, code FROM permissions') as $row) {
    $permIds[$row['code']] = (int) $row['id'];
}

$assign = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');

// --- Platform tier -----------------------------------------------------

// Super Admin gets everything, including the full tenant-module catalog (for
// support/impersonation-free troubleshooting via the platform portal).
foreach ($permIds as $code => $id) {
    $assign->execute(['role_id' => $roleIds['super_admin'], 'permission_id' => $id]);
}

// Platform Manager: oversight of organizations and platform staff, not
// destructive site config or RBAC editing.
foreach (['organizations.view', 'organizations.edit', 'users.view', 'users.create', 'users.edit', 'audit.view'] as $code) {
    $assign->execute(['role_id' => $roleIds['platform_manager'], 'permission_id' => $permIds[$code]]);
}

// Platform Accountant: read-only cross-tenant financial/audit visibility.
foreach (['organizations.view', 'audit.view'] as $code) {
    $assign->execute(['role_id' => $roleIds['platform_accountant'], 'permission_id' => $permIds[$code]]);
}

// --- Tenant tier ---------------------------------------------------------

// Farm Owner: full operational access within their organization, plus 'team'
// (invite/manage their own farm's staff) and their own org's audit trail.
$ownerModules = ['farms', 'crops', 'livestock', 'workers', 'finance', 'procurement', 'inventory', 'assets', 'reports', 'traceability', 'media'];
foreach ($ownerModules as $module) {
    foreach ($actions as $action) {
        $code = "$module.$action";
        if (isset($permIds[$code])) {
            $assign->execute(['role_id' => $roleIds['farm_owner'], 'permission_id' => $permIds[$code]]);
        }
    }
}
foreach ($actions as $action) {
    $assign->execute(['role_id' => $roleIds['farm_owner'], 'permission_id' => $permIds["team.$action"]]);
}
$assign->execute(['role_id' => $roleIds['farm_owner'], 'permission_id' => $permIds['audit.view']]);

// Farm Manager: operate farms/crops/livestock/workers/inventory, view finance/reports.
$managerFull = ['farms', 'crops', 'livestock', 'workers', 'inventory', 'traceability', 'media'];
foreach ($managerFull as $module) {
    foreach ($actions as $action) {
        $code = "$module.$action";
        if (isset($permIds[$code])) {
            $assign->execute(['role_id' => $roleIds['farm_manager'], 'permission_id' => $permIds[$code]]);
        }
    }
}
foreach (['finance.view', 'reports.view', 'procurement.view', 'assets.view'] as $code) {
    $assign->execute(['role_id' => $roleIds['farm_manager'], 'permission_id' => $permIds[$code]]);
}

// Agronomist: crops full, farms view.
foreach ($actions as $action) {
    $assign->execute(['role_id' => $roleIds['agronomist'], 'permission_id' => $permIds["crops.$action"]]);
}
$assign->execute(['role_id' => $roleIds['agronomist'], 'permission_id' => $permIds['farms.view']]);
$assign->execute(['role_id' => $roleIds['agronomist'], 'permission_id' => $permIds['traceability.view']]);
$assign->execute(['role_id' => $roleIds['agronomist'], 'permission_id' => $permIds['reports.view']]);

// Livestock Manager: livestock full, farms view.
foreach ($actions as $action) {
    $assign->execute(['role_id' => $roleIds['livestock_manager'], 'permission_id' => $permIds["livestock.$action"]]);
}
$assign->execute(['role_id' => $roleIds['livestock_manager'], 'permission_id' => $permIds['farms.view']]);
$assign->execute(['role_id' => $roleIds['livestock_manager'], 'permission_id' => $permIds['traceability.view']]);
$assign->execute(['role_id' => $roleIds['livestock_manager'], 'permission_id' => $permIds['reports.view']]);

// Store Manager: inventory + procurement full.
foreach (['inventory', 'procurement'] as $module) {
    foreach ($actions as $action) {
        $assign->execute(['role_id' => $roleIds['store_manager'], 'permission_id' => $permIds["$module.$action"]]);
    }
}
$assign->execute(['role_id' => $roleIds['store_manager'], 'permission_id' => $permIds['reports.view']]);

// Accountant (farm-level): finance full, reports full, view procurement.
foreach (['finance', 'reports'] as $module) {
    foreach ($actions as $action) {
        $assign->execute(['role_id' => $roleIds['accountant'], 'permission_id' => $permIds["$module.$action"]]);
    }
}
$assign->execute(['role_id' => $roleIds['accountant'], 'permission_id' => $permIds['procurement.view']]);

// Field Worker: view farms/crops/livestock, create own worker tasks (workers.create/edit as proxy for tasks/attendance).
foreach (['farms.view', 'crops.view', 'livestock.view', 'workers.view', 'workers.create', 'workers.edit'] as $code) {
    $assign->execute(['role_id' => $roleIds['field_worker'], 'permission_id' => $permIds[$code]]);
}

// Supplier / Customer: minimal, view-only on procurement/reports respectively.
$assign->execute(['role_id' => $roleIds['supplier'], 'permission_id' => $permIds['procurement.view']]);
$assign->execute(['role_id' => $roleIds['customer'], 'permission_id' => $permIds['traceability.view']]);

// Default Super Admin user (change password after first login). Platform-tier:
// organization_id stays NULL. Logs in at /platform/login, not /login.
$adminEmail = 'admin@afrisap.test';
$exists = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$exists->execute(['email' => $adminEmail]);
if (!$exists->fetch()) {
    $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role_id, status, mfa_enabled)
        VALUES (:name, :email, :password_hash, :role_id, :status, :mfa_enabled)');
    $insert->execute([
        'name' => 'Super Admin',
        'email' => $adminEmail,
        'password_hash' => password_hash('ChangeMe123!', PASSWORD_BCRYPT),
        'role_id' => $roleIds['super_admin'],
        'status' => 'active',
        'mfa_enabled' => 0,
    ]);
    echo "Created default Super Admin user: $adminEmail / ChangeMe123! (platform login, MFA off, change password immediately)\n";
} else {
    echo "Admin user already exists, skipped.\n";
}

echo "Seeding complete.\n";
