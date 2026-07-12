<?php
require_once __DIR__ . '/includes/auth-check.php';

// Platform-only, hard check (not just the require_permission() gate below on
// the edit page) -- roles are shared, global infrastructure. A tenant role's
// permissions apply to every organization using that role, so no tenant
// user, regardless of what they're granted, should even see this list.
if (!is_platform_user()) {
    http_response_code(403);
    exit('403 Forbidden — roles are managed platform-wide, not per organization.');
}

$roles = db()->query(
    "SELECT r.*, COUNT(rp.permission_id) AS permission_count
     FROM roles r LEFT JOIN role_permissions rp ON rp.role_id = r.id
     GROUP BY r.id ORDER BY r.scope, r.name"
)->fetchAll();

$pageTitle = 'Roles & Permissions';
$activePage = 'roles';
require __DIR__ . '/includes/header.php';
?>

<h1>Roles &amp; Permissions</h1>
<p class="muted">Each role's permissions apply to every user with that role, across every organization. Changes here are platform-wide.</p>

<table>
    <thead><tr><th>Name</th><th>Pool</th><th>Permissions</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($roles as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td>
                <td><?= e(ucfirst($r['scope'])) ?></td>
                <td><?= (int) $r['permission_count'] ?></td>
                <td><a href="<?= BASE_URL ?>/admin/role-edit.php?id=<?= (int) $r['id'] ?>">Edit permissions</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
