<?php
require_once __DIR__ . '/includes/auth-check.php';

// Platform-only: an audit trail spanning every organization is exactly the
// kind of thing that must never be visible to one tenant, let alone to
// another tenant's staff. Tenant users get no audit-log.php link in the nav.
if (!is_platform_user()) {
    http_response_code(403);
    exit('403 Forbidden — the audit log is a platform-only view.');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$table = trim($_GET['table'] ?? '');
$where = '';
$params = [];
if ($table !== '') {
    $where = 'WHERE al.table_name = :table';
    $params['table'] = $table;
}

$totalStmt = db()->prepare("SELECT COUNT(*) FROM audit_logs al $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare(
    "SELECT al.*, u.name AS user_name, o.name AS organization_name
     FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     LEFT JOIN organizations o ON o.id = al.organization_id
     $where
     ORDER BY al.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$tables = db()->query('SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Audit Log';
$activePage = 'audit-log';
require __DIR__ . '/includes/header.php';
?>

<h1>Audit Log</h1>
<p class="muted">Every create/update/delete/status-change action recorded across the platform, newest first.</p>

<form method="GET" action="<?= BASE_URL ?>/admin/audit-log.php" style="margin-bottom:1.5rem;">
    <label>Table</label>
    <select name="table" onchange="this.form.submit()" style="padding:0.5rem;">
        <option value="">All tables</option>
        <?php foreach ($tables as $t): ?>
            <option value="<?= e($t) ?>" <?= $table === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<table>
    <thead>
        <tr><th>When</th><th>User</th><th>Organization</th><th>Action</th><th>Table</th><th>Record</th><th>Change</th></tr>
    </thead>
    <tbody>
        <?php if (!$logs): ?><tr><td colspan="7">No audit entries yet.</td></tr><?php endif; ?>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= e($log['created_at']) ?></td>
                <td><?= e($log['user_name'] ?? 'System') ?></td>
                <td><?= e($log['organization_name'] ?? '—') ?></td>
                <td><?= e($log['action']) ?></td>
                <td><?= e($log['table_name']) ?></td>
                <td><?= e($log['record_id'] ?? '—') ?></td>
                <td style="font-family:monospace; font-size:0.75rem; max-width:320px; overflow-wrap:anywhere;">
                    <?php if ($log['old_value']): ?><div>old: <?= e($log['old_value']) ?></div><?php endif; ?>
                    <?php if ($log['new_value']): ?><div>new: <?= e($log['new_value']) ?></div><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($totalPages > 1): ?>
<div style="margin-top:1rem; display:flex; gap:0.5rem;">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="<?= BASE_URL ?>/admin/audit-log.php?page=<?= $p ?><?= $table !== '' ? '&table=' . urlencode($table) : '' ?>"
           class="btn <?= $p === $page ? '' : 'btn-outline' ?>" style="padding:0.3rem 0.6rem; font-size:0.8rem;"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
