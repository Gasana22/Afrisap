<?php
require_once __DIR__ . '/includes/auth-check.php';

if (!is_platform_user()) {
    http_response_code(403);
    exit('403 Forbidden — roles are managed platform-wide, not per organization.');
}

$roleId = (int) ($_GET['id'] ?? 0);
$roleStmt = db()->prepare('SELECT * FROM roles WHERE id = :id');
$roleStmt->execute(['id' => $roleId]);
$role = $roleStmt->fetch();

if (!$role) {
    http_response_code(404);
    exit('404 — role not found.');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('roles.manage');

    $before = db()->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :id');
    $before->execute(['id' => $roleId]);
    $beforeIds = $before->fetchAll(PDO::FETCH_COLUMN);

    $selectedIds = array_map('intval', $_POST['permissions'] ?? []);

    db()->beginTransaction();
    db()->prepare('DELETE FROM role_permissions WHERE role_id = :id')->execute(['id' => $roleId]);
    $insert = db()->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
    foreach ($selectedIds as $permissionId) {
        $insert->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }
    db()->commit();

    audit_log('update_permissions', 'roles', (string) $roleId, ['permission_ids' => $beforeIds], ['permission_ids' => $selectedIds]);

    flash('success', 'Permissions updated for ' . $role['name'] . '.');
    redirect('/admin/roles.php');
}

$permissions = db()->query('SELECT * FROM permissions ORDER BY module, code')->fetchAll();
$permissionsByModule = [];
foreach ($permissions as $p) {
    $permissionsByModule[$p['module']][] = $p;
}

$assignedStmt = db()->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :id');
$assignedStmt->execute(['id' => $roleId]);
$assignedIds = array_map('intval', $assignedStmt->fetchAll(PDO::FETCH_COLUMN));

$pageTitle = 'Edit Role: ' . $role['name'];
$activePage = 'roles';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/roles.php">&larr; All roles</a></p>
<h1>Edit Role: <?= e($role['name']) ?></h1>
<p class="muted"><?= e(ucfirst($role['scope'])) ?> role. <?= e($role['description'] ?? '') ?></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/admin/role-edit.php?id=<?= $roleId ?>">
    <?php foreach ($permissionsByModule as $module => $modulePermissions): ?>
        <div class="card" style="margin-bottom:1rem;">
            <h2 style="margin-top:0; font-size:1rem; text-transform:capitalize;"><?= e($module) ?></h2>
            <div class="checkbox-grid">
                <?php foreach ($modulePermissions as $p): ?>
                    <label>
                        <input type="checkbox" name="permissions[]" value="<?= (int) $p['id'] ?>" <?= in_array((int) $p['id'], $assignedIds, true) ? 'checked' : '' ?>>
                        <?= e($p['code']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn">Save permissions</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
