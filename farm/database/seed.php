<?php
/**
 * Seed data: base roles/permissions, one demo tenant organization, one
 * platform Super Admin and one tenant Farm Owner login, plus the reference
 * lists (crop types, seasons) modules depend on. Safe to re-run -- every
 * insert checks for an existing row first.
 *
 * Run after database/migrate.php:
 *
 *   php database/seed.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = db();

function findOrCreateRole(PDO $pdo, string $name, string $slug, string $scope, string $description): int
{
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    if ($id = $stmt->fetchColumn()) {
        return (int) $id;
    }
    $pdo->prepare(
        'INSERT INTO roles (name, slug, description, scope, is_system) VALUES (:name, :slug, :desc, :scope, 1)'
    )->execute(['name' => $name, 'slug' => $slug, 'desc' => $description, 'scope' => $scope]);
    return (int) $pdo->lastInsertId();
}

function findOrCreatePermission(PDO $pdo, string $code, string $module, string $description): int
{
    $stmt = $pdo->prepare('SELECT id FROM permissions WHERE code = :code');
    $stmt->execute(['code' => $code]);
    if ($id = $stmt->fetchColumn()) {
        return (int) $id;
    }
    $pdo->prepare(
        'INSERT INTO permissions (code, module, description) VALUES (:code, :module, :desc)'
    )->execute(['code' => $code, 'module' => $module, 'desc' => $description]);
    return (int) $pdo->lastInsertId();
}

function grant(PDO $pdo, int $roleId, int $permissionId): void
{
    $pdo->prepare(
        'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
    )->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
}

// --- Roles -------------------------------------------------------------
$superAdminRole = findOrCreateRole($pdo, 'Super Admin', 'super_admin', 'platform', 'Full platform administration and oversight');
$farmOwnerRole  = findOrCreateRole($pdo, 'Farm Owner', 'farm_owner', 'tenant', 'Owns the organization and all its farms');
$farmManagerRole = findOrCreateRole($pdo, 'Farm Manager', 'farm_manager', 'tenant', 'Runs day-to-day farm operations');
$accountantRole = findOrCreateRole($pdo, 'Accountant', 'accountant', 'tenant', 'Manages finance and procurement records');
$workerRole     = findOrCreateRole($pdo, 'Worker', 'worker', 'tenant', 'Field/farm worker, no back-office access');

// --- Permissions ---------------------------------------------------------
$permissions = [
    'farms.manage'         => ['farms', 'Create and edit farms, blocks, plots'],
    'crops.manage'         => ['crops', 'Manage crop cycles and related records'],
    'livestock.manage'     => ['livestock', 'Manage animals and related records'],
    'workers.manage'       => ['workers', 'Manage worker records, attendance, tasks, payroll'],
    'finance.manage'       => ['finance', 'Manage income and expenses'],
    'procurement.manage'   => ['procurement', 'Manage suppliers and purchase orders'],
    'inventory.manage'     => ['inventory', 'Manage inventory items and stock movements'],
    'assets.manage'        => ['assets', 'Manage assets and maintenance records'],
    'traceability.manage'  => ['traceability', 'Manage trace batches, documents, approvals'],
    'users.manage'         => ['users', 'Manage user accounts within the organization'],
    'media.manage'         => ['media', 'Upload and manage media files'],
];

$permissionIds = [];
foreach ($permissions as $code => [$module, $desc]) {
    $permissionIds[$code] = findOrCreatePermission($pdo, $code, $module, $desc);
}

$grants = [
    $superAdminRole   => array_keys($permissions),
    $farmOwnerRole    => array_keys($permissions),
    $farmManagerRole  => ['crops.manage', 'livestock.manage', 'workers.manage', 'inventory.manage', 'assets.manage', 'traceability.manage', 'media.manage'],
    $accountantRole   => ['finance.manage', 'procurement.manage'],
    $workerRole       => [],
];

foreach ($grants as $roleId => $codes) {
    foreach ($codes as $code) {
        grant($pdo, $roleId, $permissionIds[$code]);
    }
}

// --- Demo organization + users -------------------------------------------
$orgStmt = $pdo->prepare('SELECT id FROM organizations WHERE name = :name');
$orgStmt->execute(['name' => 'Demo Farm Cooperative']);
$orgId = $orgStmt->fetchColumn();

if (!$orgId) {
    $pdo->prepare('INSERT INTO organizations (name, status) VALUES (:name, "active")')
        ->execute(['name' => 'Demo Farm Cooperative']);
    $orgId = (int) $pdo->lastInsertId();
} else {
    $orgId = (int) $orgId;
}

function findOrCreateUser(PDO $pdo, string $name, string $email, string $password, int $roleId, ?int $organizationId): int
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    if ($id = $stmt->fetchColumn()) {
        return (int) $id;
    }
    $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role_id, organization_id, status, mfa_enabled)
         VALUES (:name, :email, :hash, :role_id, :org_id, "active", 0)'
    )->execute([
        'name' => $name,
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
        'role_id' => $roleId,
        'org_id' => $organizationId,
    ]);
    return (int) $pdo->lastInsertId();
}

// MFA is off for these seeded accounts so they're usable immediately --
// otp_codes delivery is dev-only (see includes/auth.php generate_and_send_otp()).
$superAdminId = findOrCreateUser($pdo, 'Platform Super Admin', 'admin@afrisap.test', 'ChangeMe123!', $superAdminRole, null);
$farmOwnerId  = findOrCreateUser($pdo, 'Demo Farm Owner', 'owner@afrisap.test', 'ChangeMe123!', $farmOwnerRole, $orgId);

$pdo->prepare('UPDATE organizations SET owner_user_id = :uid WHERE id = :id AND owner_user_id IS NULL')
    ->execute(['uid' => $farmOwnerId, 'id' => $orgId]);

// --- Reference data: crop types + a current season -----------------------
$cropTypes = ['Maize', 'Beans', 'Coffee', 'Tea', 'Irish Potato', 'Cassava', 'Rice', 'Banana'];
foreach ($cropTypes as $cropType) {
    $pdo->prepare('INSERT IGNORE INTO crop_types (name) VALUES (:name)')->execute(['name' => $cropType]);
}

$seasonStmt = $pdo->prepare('SELECT id FROM seasons WHERE name = :name');
$seasonStmt->execute(['name' => 'Season A ' . date('Y')]);
if (!$seasonStmt->fetchColumn()) {
    $pdo->prepare('INSERT INTO seasons (name, start_date, end_date) VALUES (:name, :start, :end)')
        ->execute([
            'name' => 'Season A ' . date('Y'),
            'start' => date('Y') . '-09-01',
            'end' => date('Y') . '-02-28',
        ]);
}

echo "Seed complete.\n";
echo "Platform Super Admin: admin@afrisap.test / ChangeMe123!\n";
echo "Demo Farm Owner:      owner@afrisap.test / ChangeMe123!  (organization: Demo Farm Cooperative)\n";
echo "Change these passwords before any shared/production use.\n";
