<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('crops.manage');

    $plotId = (int) ($_POST['plot_id'] ?? 0);
    $cropTypeId = (int) ($_POST['crop_type_id'] ?? 0);
    $seasonId = ($_POST['season_id'] ?? '') !== '' ? (int) $_POST['season_id'] : null;
    $budget = ($_POST['budget'] ?? '') !== '' ? (float) $_POST['budget'] : null;
    $expectedYield = ($_POST['expected_yield'] ?? '') !== '' ? (float) $_POST['expected_yield'] : null;
    $startDate = ($_POST['start_date'] ?? '') ?: null;

    // Confirm the plot belongs to a farm this user can see.
    $plotCheck = null;
    if ($plotId && $farmIds) {
        $stmt = db()->prepare(
            'SELECT p.id FROM plots p JOIN blocks b ON b.id = p.block_id
             WHERE p.id = ? AND b.farm_id IN (' . in_placeholders($farmIds) . ')'
        );
        $stmt->execute(array_merge([$plotId], $farmIds));
        $plotCheck = $stmt->fetch();
    }

    if (!$plotCheck || !$cropTypeId) {
        $error = 'A valid plot and crop type are required.';
    } else {
        $batchCode = 'CYC' . strtoupper(bin2hex(random_bytes(4)));
        db()->prepare(
            'INSERT INTO crop_cycles (batch_code, plot_id, crop_type_id, season_id, budget, expected_yield, start_date, status)
             VALUES (:code, :plot_id, :crop_type_id, :season_id, :budget, :expected_yield, :start_date, "planning")'
        )->execute([
            'code' => $batchCode,
            'plot_id' => $plotId,
            'crop_type_id' => $cropTypeId,
            'season_id' => $seasonId,
            'budget' => $budget,
            'expected_yield' => $expectedYield,
            'start_date' => $startDate,
        ]);

        $newCycleId = (int) db()->lastInsertId();
        create_trace_batch('crop', $batchCode, $newCycleId);
        audit_log('create', 'crop_cycles', (string) $newCycleId, null, ['plot_id' => $plotId, 'batch_code' => $batchCode]);

        flash('success', 'Crop cycle created.');
        redirect('/admin/crops.php');
    }
}

$cropCycles = [];
$plots = $cropTypes = $seasons = [];

if ($farmIds) {
    $stmt = db()->prepare(
        'SELECT cc.*, ct.name AS crop_type_name, p.plot_code, b.name AS block_name, f.name AS farm_name
         FROM crop_cycles cc
         JOIN plots p ON p.id = cc.plot_id
         JOIN blocks b ON b.id = p.block_id
         JOIN farms f ON f.id = b.farm_id
         JOIN crop_types ct ON ct.id = cc.crop_type_id
         WHERE f.id IN (' . in_placeholders($farmIds) . ')
         ORDER BY cc.created_at DESC'
    );
    $stmt->execute($farmIds);
    $cropCycles = $stmt->fetchAll();

    $plotStmt = db()->prepare(
        'SELECT p.id, p.plot_code, b.name AS block_name, f.name AS farm_name
         FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
         WHERE f.id IN (' . in_placeholders($farmIds) . ')
         ORDER BY f.name, b.name, p.plot_code'
    );
    $plotStmt->execute($farmIds);
    $plots = $plotStmt->fetchAll();
}

$cropTypes = db()->query('SELECT id, name FROM crop_types ORDER BY name')->fetchAll();
$seasons = db()->query('SELECT id, name FROM seasons ORDER BY start_date DESC')->fetchAll();

$statusLabels = [
    'planning' => 'Planning', 'procurement' => 'Procurement', 'nursery' => 'Nursery',
    'field' => 'Field', 'monitoring' => 'Monitoring', 'harvested' => 'Harvested', 'closed' => 'Closed',
];

$pageTitle = 'Crop Cycles';
$activePage = 'crops';
require __DIR__ . '/includes/header.php';
?>

<h1>Crop Cycles</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if (!$plots): ?>
    <div class="alert alert-error">No plots available yet. <a href="<?= BASE_URL ?>/admin/farms.php">Set up a farm, block and plot</a> first.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:480px;">
    <h2 style="margin-top:0; font-size:1rem;">Start a crop cycle</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/crops.php">
        <label>Plot</label>
        <select name="plot_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($plots as $plot): ?>
                <option value="<?= (int) $plot['id'] ?>"><?= e($plot['farm_name'] . ' / ' . $plot['block_name'] . ' / ' . $plot['plot_code']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Crop type</label>
        <select name="crop_type_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($cropTypes as $ct): ?>
                <option value="<?= (int) $ct['id'] ?>"><?= e($ct['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Season (optional)</label>
        <select name="season_id" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">None</option>
            <?php foreach ($seasons as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Budget</label>
        <input type="number" step="0.01" name="budget" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

        <label>Expected yield</label>
        <input type="number" step="0.01" name="expected_yield" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

        <label>Start date</label>
        <input type="date" name="start_date" style="width:100%; padding:0.5rem; margin-bottom:1rem;">

        <button type="submit" class="btn">Create</button>
    </form>
</div>

<table>
    <thead>
        <tr><th>Batch code</th><th>Plot</th><th>Crop type</th><th>Status</th><th>Start date</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (!$cropCycles): ?><tr><td colspan="6">No crop cycles yet.</td></tr><?php endif; ?>
        <?php foreach ($cropCycles as $cc): ?>
            <tr>
                <td><?= e($cc['batch_code']) ?></td>
                <td><?= e($cc['farm_name'] . ' / ' . $cc['block_name'] . ' / ' . $cc['plot_code']) ?></td>
                <td><?= e($cc['crop_type_name']) ?></td>
                <td><?= e($statusLabels[$cc['status']] ?? $cc['status']) ?></td>
                <td><?= e($cc['start_date'] ?? '—') ?></td>
                <td><a href="<?= BASE_URL ?>/admin/crop-view.php?id=<?= (int) $cc['id'] ?>">Manage</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
