<?php
require_once __DIR__ . '/includes/auth-check.php';

if (!is_platform_user()) {
    http_response_code(403);
    exit('403 Forbidden — organizations are managed platform-wide, not per organization.');
}

// Read-only by design: platform staff can see that an organization exists,
// how many farms/users it has, and who owns it -- but never its farm
// operational data (crops, livestock, workers, finance, ...). That's the
// whole point of this page existing instead of just pointing admins at
// admin/farms.php.
$organizations = db()->query(
    "SELECT o.*, u.name AS owner_name, u.email AS owner_email,
            (SELECT COUNT(*) FROM farms f WHERE f.organization_id = o.id) AS farm_count,
            (SELECT COUNT(*) FROM users tu WHERE tu.organization_id = o.id AND tu.status = 'active') AS user_count
     FROM organizations o
     LEFT JOIN users u ON u.id = o.owner_user_id
     ORDER BY o.created_at DESC"
)->fetchAll();

$pageTitle = 'Organizations';
$activePage = 'organizations';
require __DIR__ . '/includes/header.php';
?>

<h1>Organizations</h1>
<p class="muted">Read-only overview. Farm-level operations belong to each organization's own team, not the Admin Portal.</p>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Owner</th>
            <th>Farms</th>
            <th>Active users</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$organizations): ?><tr><td colspan="6">No organizations yet.</td></tr><?php endif; ?>
        <?php foreach ($organizations as $org): ?>
            <tr>
                <td><?= e($org['name']) ?></td>
                <td><?= $org['owner_name'] ? e($org['owner_name']) . ' (' . e($org['owner_email']) . ')' : '—' ?></td>
                <td><?= (int) $org['farm_count'] ?></td>
                <td><?= (int) $org['user_count'] ?></td>
                <td><?= e($org['status']) ?></td>
                <td><?= e($org['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
