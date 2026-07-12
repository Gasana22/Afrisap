<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('assets.manage');

    $farmId = (int) ($_POST['farm_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if (!in_array($farmId, $farmIds, true) || $name === '') {
        $error = 'A valid farm and name are required.';
    } else {
        db()->prepare(
            'INSERT INTO assets (farm_id, type, name, identifier, purchase_date, purchase_value, status, notes)
             VALUES (:farm, :type, :name, :identifier, :purchase_date, :value, "active", :notes)'
        )->execute([
            'farm' => $farmId,
            'type' => $_POST['type'] ?? 'equipment',
            'name' => $name,
            'identifier' => trim($_POST['identifier'] ?? '') ?: null,
            'purchase_date' => $_POST['purchase_date'] ?: null,
            'value' => $_POST['purchase_value'] !== '' ? (float) $_POST['purchase_value'] : null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Asset added.');
        redirect('/admin/assets.php');
    }
}

$assets = [];
$farms = [];
if ($farmIds) {
    $stmt = db()->prepare(
        'SELECT a.*, f.name AS farm_name FROM assets a JOIN farms f ON f.id = a.farm_id
         WHERE a.farm_id IN (' . in_placeholders($farmIds) . ') ORDER BY a.created_at DESC'
    );
    $stmt->execute($farmIds);
    $assets = $stmt->fetchAll();

    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();
}

$pageTitle = 'Assets';
$activePage = 'assets';
require __DIR__ . '/includes/header.php';
?>

<h1>Assets</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if (!$farms): ?>
    <div class="alert alert-error">No farms available yet. <a href="<?= BASE_URL ?>/admin/farms.php">Add a farm</a> first.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Add an asset</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/assets.php">
        <label>Farm</label>
        <select name="farm_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
        </select>
        <label>Type</label>
        <select name="type" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="vehicle">Vehicle</option><option value="machinery">Machinery</option>
            <option value="building">Building</option><option value="irrigation">Irrigation</option>
        </select>
        <label>Name</label>
        <input type="text" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Identifier (plate/serial)</label>
        <input type="text" name="identifier" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Purchase date</label>
        <input type="date" name="purchase_date" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Purchase value</label>
        <input type="number" step="0.01" name="purchase_value" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Notes</label>
        <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Add</button>
    </form>
</div>

<table>
    <thead><tr><th>Name</th><th>Type</th><th>Farm</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php if (!$assets): ?><tr><td colspan="5">No assets yet.</td></tr><?php endif; ?>
        <?php foreach ($assets as $a): ?>
            <tr>
                <td><?= e($a['name']) ?></td>
                <td><?= e($a['type']) ?></td>
                <td><?= e($a['farm_name']) ?></td>
                <td><?= e($a['status']) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/asset-view.php?id=<?= (int) $a['id'] ?>">Manage</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
