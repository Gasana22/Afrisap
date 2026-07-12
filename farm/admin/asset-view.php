<?php
require_once __DIR__ . '/includes/auth-check.php';

$assetId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT a.*, f.name AS farm_name, f.organization_id FROM assets a JOIN farms f ON f.id = a.farm_id WHERE a.id = :id'
);
$stmt->execute(['id' => $assetId]);
$asset = $stmt->fetch();

if (!$asset || (!is_platform_user() && (int) $asset['organization_id'] !== (int) current_organization_id())) {
    http_response_code(404);
    exit('404 — asset not found.');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('assets.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_maintenance') {
        db()->prepare(
            'INSERT INTO asset_maintenance (asset_id, maintenance_date, description, cost, next_due_date, performed_by)
             VALUES (:asset, :date, :description, :cost, :next_due, :performed_by)'
        )->execute([
            'asset' => $assetId,
            'date' => ($_POST['maintenance_date'] ?? '') ?: date('Y-m-d'),
            'description' => trim($_POST['description'] ?? ''),
            'cost' => ($_POST['cost'] ?? '') !== '' ? (float) $_POST['cost'] : null,
            'next_due' => ($_POST['next_due_date'] ?? '') ?: null,
            'performed_by' => trim($_POST['performed_by'] ?? '') ?: null,
        ]);
        flash('success', 'Maintenance recorded.');
        redirect('/admin/asset-view.php?id=' . $assetId);
    }

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['active', 'under_maintenance', 'retired'], true)) {
            db()->prepare('UPDATE assets SET status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $assetId]);
            flash('success', 'Status updated.');
        }
        redirect('/admin/asset-view.php?id=' . $assetId);
    }
}

$maintenanceStmt = db()->prepare('SELECT * FROM asset_maintenance WHERE asset_id = :asset ORDER BY maintenance_date DESC');
$maintenanceStmt->execute(['asset' => $assetId]);
$maintenanceRecords = $maintenanceStmt->fetchAll();

$pageTitle = $asset['name'];
$activePage = 'assets';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/assets.php">&larr; All assets</a></p>
<h1><?= e($asset['name']) ?></h1>
<p class="muted"><?= e($asset['type']) ?><?= $asset['identifier'] ? ' · ' . e($asset['identifier']) : '' ?> · <?= e($asset['farm_name']) ?></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Status</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/asset-view.php?id=<?= $assetId ?>" style="display:flex; gap:0.5rem;">
        <input type="hidden" name="action" value="update_status">
        <select name="status" style="flex:1; padding:0.5rem;">
            <option value="active" <?= $asset['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="under_maintenance" <?= $asset['status'] === 'under_maintenance' ? 'selected' : '' ?>>Under maintenance</option>
            <option value="retired" <?= $asset['status'] === 'retired' ? 'selected' : '' ?>>Retired</option>
        </select>
        <button type="submit" class="btn">Update</button>
    </form>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Maintenance history</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Description</th><th>Cost</th><th>Next due</th><th>Performed by</th></tr></thead>
        <tbody>
            <?php if (!$maintenanceRecords): ?><tr><td colspan="5">None yet.</td></tr><?php endif; ?>
            <?php foreach ($maintenanceRecords as $m): ?>
                <tr>
                    <td><?= e($m['maintenance_date']) ?></td>
                    <td><?= e($m['description']) ?></td>
                    <td><?= e($m['cost'] ?? '—') ?></td>
                    <td><?= e($m['next_due_date'] ?? '—') ?></td>
                    <td><?= e($m['performed_by'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Log maintenance</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/asset-view.php?id=<?= $assetId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_maintenance">
            <label>Date</label>
            <input type="date" name="maintenance_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Description</label>
            <input type="text" name="description" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Cost</label>
            <input type="number" step="0.01" name="cost" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Next due date</label>
            <input type="date" name="next_due_date" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Performed by</label>
            <input type="text" name="performed_by" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
