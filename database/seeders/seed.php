<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::connection();

$roles = [
    ['system_administrator', 'System Administrator', 'Full platform access and configuration', 1],
    ['farm_owner', 'Farm Owner', 'Owns farms, views full performance', 1],
    ['farm_manager', 'Farm Manager', 'Operates and supervises day-to-day farm activity', 1],
    ['agronomist', 'Agronomist', 'Crop management specialist', 1],
    ['livestock_manager', 'Livestock Manager', 'Animal management specialist', 1],
    ['store_manager', 'Store Manager', 'Inventory and procurement', 1],
    ['accountant', 'Accountant', 'Finance and reporting', 1],
    ['field_worker', 'Field Worker', 'Executes daily field/farm tasks', 1],
    ['supplier', 'Supplier', 'External input supplier, delivers inputs', 1],
    ['customer', 'Customer', 'External buyer, purchases outputs', 1],
];

$roleIds = [];
$stmt = $pdo->prepare('INSERT INTO roles (slug, name, description, is_system) VALUES (:slug, :name, :description, :is_system)
    ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)');
foreach ($roles as [$slug, $name, $description, $isSystem]) {
    $stmt->execute(['slug' => $slug, 'name' => $name, 'description' => $description, 'is_system' => $isSystem]);
}
foreach ($pdo->query('SELECT id, slug FROM roles') as $row) {
    $roleIds[$row['slug']] = (int) $row['id'];
}

$modules = ['users', 'roles', 'settings', 'audit', 'farms', 'crops', 'livestock', 'workers', 'finance', 'procurement', 'inventory', 'assets', 'reports', 'traceability'];
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

// System Administrator gets everything.
$assign = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
foreach ($permIds as $code => $id) {
    $assign->execute(['role_id' => $roleIds['system_administrator'], 'permission_id' => $id]);
}

// Farm Owner: view everything, create/edit on operational modules, no user/role management.
$ownerModules = ['farms', 'crops', 'livestock', 'workers', 'finance', 'procurement', 'inventory', 'assets', 'reports', 'traceability'];
foreach ($ownerModules as $module) {
    foreach ($actions as $action) {
        $code = "$module.$action";
        if (isset($permIds[$code])) {
            $assign->execute(['role_id' => $roleIds['farm_owner'], 'permission_id' => $permIds[$code]]);
        }
    }
}
$assign->execute(['role_id' => $roleIds['farm_owner'], 'permission_id' => $permIds['audit.view']]);

// Farm Manager: operate farms/crops/livestock/workers/inventory, view finance/reports.
$managerFull = ['farms', 'crops', 'livestock', 'workers', 'inventory', 'traceability'];
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

// Livestock Manager: livestock full, farms view.
foreach ($actions as $action) {
    $assign->execute(['role_id' => $roleIds['livestock_manager'], 'permission_id' => $permIds["livestock.$action"]]);
}
$assign->execute(['role_id' => $roleIds['livestock_manager'], 'permission_id' => $permIds['farms.view']]);
$assign->execute(['role_id' => $roleIds['livestock_manager'], 'permission_id' => $permIds['traceability.view']]);

// Store Manager: inventory + procurement full.
foreach (['inventory', 'procurement'] as $module) {
    foreach ($actions as $action) {
        $assign->execute(['role_id' => $roleIds['store_manager'], 'permission_id' => $permIds["$module.$action"]]);
    }
}

// Accountant: finance full, reports full, view procurement.
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

// Default System Administrator user (change password after first login).
$adminEmail = 'admin@afrisap.test';
$exists = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$exists->execute(['email' => $adminEmail]);
if (!$exists->fetch()) {
    $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role_id, status, mfa_enabled)
        VALUES (:name, :email, :password_hash, :role_id, :status, :mfa_enabled)');
    $insert->execute([
        'name' => 'System Administrator',
        'email' => $adminEmail,
        'password_hash' => password_hash('ChangeMe123!', PASSWORD_BCRYPT),
        'role_id' => $roleIds['system_administrator'],
        'status' => 'active',
        'mfa_enabled' => 0,
    ]);
    echo "Created default admin user: $adminEmail / ChangeMe123! (MFA off, change password immediately)\n";
} else {
    echo "Admin user already exists, skipped.\n";
}

echo "Seeding complete.\n";
