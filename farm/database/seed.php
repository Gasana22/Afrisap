<?php
/**
 * Seed data: base roles/permissions, two demo tenant organizations (to make
 * the multi-tenant, many-farms/many-organizations design tangible out of the
 * box), a platform Admin Portal login, tenant Farm Portal logins, plus the
 * reference lists (crop types, seasons) modules depend on. Safe to re-run --
 * every insert checks for an existing row first.
 *
 * Two portals, two role pools (roles.scope):
 *   platform -- Super Admin, Manager, Accountant: keep the SITE running.
 *               organization_id IS NULL. Log in at /admin-login.php.
 *   tenant   -- Farm Owner, Farm Manager, Agronomist, Livestock Manager,
 *               Store Manager, Farm Accountant, Field Worker, Viewer: use
 *               the site to manage THEIR OWN farm(s)/organization.
 *               organization_id is set. Log in at /login.php.
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

// --- Roles -----------------------------------------------------------------
// Platform pool (scope=platform, organization_id NULL) -- the Admin Portal,
// for the small internal team that keeps the whole system operational
// across every tenant.
$superAdminRole = findOrCreateRole($pdo, 'Super Admin', 'super_admin', 'platform', 'Full platform administration and oversight across every organization');
$managerRole     = findOrCreateRole($pdo, 'Manager', 'manager', 'platform', 'Platform operations -- supports every tenant, cannot manage platform user accounts');
$accountantRole  = findOrCreateRole($pdo, 'Accountant', 'accountant', 'platform', 'Platform-wide finance oversight across every organization');

// Tenant pool (scope=tenant, organization_id set) -- the Farm Portal, for
// farm owners and their own staff, managing their own farm(s)/organization.
$farmOwnerRole        = findOrCreateRole($pdo, 'Farm Owner', 'farm_owner', 'tenant', 'Owns the organization and all of its farms');
$farmManagerRole      = findOrCreateRole($pdo, 'Farm Manager', 'farm_manager', 'tenant', 'Operates and supervises day-to-day farm activity');
$agronomistRole       = findOrCreateRole($pdo, 'Agronomist', 'agronomist', 'tenant', 'Crop management specialist');
$livestockManagerRole = findOrCreateRole($pdo, 'Livestock Manager', 'livestock_manager', 'tenant', 'Animal management specialist');
$storeManagerRole     = findOrCreateRole($pdo, 'Store Manager', 'store_manager', 'tenant', 'Inventory and stock management');
$farmAccountantRole   = findOrCreateRole($pdo, 'Farm Accountant', 'farm_accountant', 'tenant', "Manages the organization's own finance and procurement records");
$fieldWorkerRole      = findOrCreateRole($pdo, 'Field Worker', 'field_worker', 'tenant', 'Carries out daily field/farm activities');
$viewerRole           = findOrCreateRole($pdo, 'Viewer', 'viewer', 'tenant', 'Read-only access, no ability to create or edit records');

// --- Permissions -------------------------------------------------------------
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
    'users.manage'         => ['users', 'Manage user accounts within your pool (platform staff, or your organization)'],
    'media.manage'         => ['media', 'Upload and manage media files'],
];

$permissionIds = [];
foreach ($permissions as $code => [$module, $desc]) {
    $permissionIds[$code] = findOrCreatePermission($pdo, $code, $module, $desc);
}

$allPermissions = array_keys($permissions);

$grants = [
    // Platform pool: Super Admin can do everything, including managing
    // platform staff accounts. Manager gets the same operational reach
    // (support across every tenant) but not users.manage, so only Super
    // Admin can create/remove platform staff. Accountant is scoped to
    // platform-wide finance oversight only.
    $superAdminRole   => $allPermissions,
    $managerRole      => array_values(array_diff($allPermissions, ['users.manage'])),
    $accountantRole   => ['finance.manage', 'procurement.manage'],

    // Tenant pool: Farm Owner can do everything within their own
    // organization. The rest are narrower, matching their job.
    $farmOwnerRole        => $allPermissions,
    $farmManagerRole      => ['crops.manage', 'livestock.manage', 'workers.manage', 'inventory.manage', 'assets.manage', 'traceability.manage', 'media.manage'],
    $agronomistRole       => ['crops.manage', 'traceability.manage'],
    $livestockManagerRole => ['livestock.manage', 'traceability.manage'],
    $storeManagerRole     => ['inventory.manage'],
    $farmAccountantRole   => ['finance.manage', 'procurement.manage'],
    $fieldWorkerRole      => [],
    $viewerRole           => [],
];

foreach ($grants as $roleId => $codes) {
    foreach ($codes as $code) {
        grant($pdo, $roleId, $permissionIds[$code]);
    }
}

// --- Demo organizations + users ---------------------------------------------
// Two organizations, not one -- the point of the platform is many farms and
// many organizations, each fully isolated from the others (see farm_or_404()
// / visible_farm_ids() in includes/functions.php).

function findOrCreateOrganization(PDO $pdo, string $name): int
{
    $stmt = $pdo->prepare('SELECT id FROM organizations WHERE name = :name');
    $stmt->execute(['name' => $name]);
    if ($id = $stmt->fetchColumn()) {
        return (int) $id;
    }
    $pdo->prepare('INSERT INTO organizations (name, status) VALUES (:name, "active")')->execute(['name' => $name]);
    return (int) $pdo->lastInsertId();
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

function setOrganizationOwner(PDO $pdo, int $organizationId, int $ownerUserId): void
{
    $pdo->prepare('UPDATE organizations SET owner_user_id = :uid WHERE id = :id AND owner_user_id IS NULL')
        ->execute(['uid' => $ownerUserId, 'id' => $organizationId]);
}

$orgAId = findOrCreateOrganization($pdo, 'Demo Farm Cooperative');
$orgBId = findOrCreateOrganization($pdo, 'Green Valley Farms');

// MFA is off for these seeded accounts so they're usable immediately --
// otp_codes delivery is dev-only (see includes/auth.php generate_and_send_otp()).
$superAdminId = findOrCreateUser($pdo, 'Platform Super Admin', 'admin@afrisap.test', 'ChangeMe123!', $superAdminRole, null);
findOrCreateUser($pdo, 'Platform Manager', 'manager@afrisap.test', 'ChangeMe123!', $managerRole, null);
findOrCreateUser($pdo, 'Platform Accountant', 'finance@afrisap.test', 'ChangeMe123!', $accountantRole, null);

$orgAOwnerId = findOrCreateUser($pdo, 'Demo Farm Owner', 'owner@afrisap.test', 'ChangeMe123!', $farmOwnerRole, $orgAId);
findOrCreateUser($pdo, 'Demo Farm Manager', 'farm-manager@afrisap.test', 'ChangeMe123!', $farmManagerRole, $orgAId);
findOrCreateUser($pdo, 'Demo Field Worker', 'worker@afrisap.test', 'ChangeMe123!', $fieldWorkerRole, $orgAId);

$orgBOwnerId = findOrCreateUser($pdo, 'Green Valley Owner', 'owner2@afrisap.test', 'ChangeMe123!', $farmOwnerRole, $orgBId);

setOrganizationOwner($pdo, $orgAId, $orgAOwnerId);
setOrganizationOwner($pdo, $orgBId, $orgBOwnerId);

// --- Reference data: crop types + a current season -----------------------
// Shared across every organization (no organization_id column), matching
// the schema design -- see database/migrations/018_*.sql's comment on why
// these specifically were left as global reference lists.
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

echo "Seed complete.\n\n";
echo "Admin Portal (/admin-login.php) -- keeps the platform running:\n";
echo "  Super Admin: admin@afrisap.test / ChangeMe123!\n";
echo "  Manager:     manager@afrisap.test / ChangeMe123!\n";
echo "  Accountant:  finance@afrisap.test / ChangeMe123!\n\n";
echo "Farm Portal (/login.php) -- manages your own farm(s)/organization:\n";
echo "  Demo Farm Cooperative -- Owner: owner@afrisap.test / ChangeMe123!\n";
echo "  Demo Farm Cooperative -- Manager: farm-manager@afrisap.test / ChangeMe123!\n";
echo "  Demo Farm Cooperative -- Field Worker: worker@afrisap.test / ChangeMe123!\n";
echo "  Green Valley Farms -- Owner: owner2@afrisap.test / ChangeMe123!\n\n";
echo "Change these passwords before any shared/production use.\n";
