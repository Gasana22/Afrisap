<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmId = (int) ($_GET['id'] ?? 0);
$farm = farm_or_404($farmId);

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('farms.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_block') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $error = 'Block name is required.';
        } else {
            db()->prepare('INSERT INTO blocks (farm_id, name, description) VALUES (:farm_id, :name, :description)')
                ->execute(['farm_id' => $farmId, 'name' => $name, 'description' => $description ?: null]);
            flash('success', 'Block added.');
            redirect('/admin/farm-view.php?id=' . $farmId);
        }
    } elseif ($action === 'add_plot') {
        $blockId = (int) ($_POST['block_id'] ?? 0);
        $plotCode = trim($_POST['plot_code'] ?? '');
        $sizeHectares = $_POST['size_hectares'] !== '' ? (float) $_POST['size_hectares'] : null;
        $gpsLat = $_POST['gps_lat'] !== '' ? (float) $_POST['gps_lat'] : null;
        $gpsLng = $_POST['gps_lng'] !== '' ? (float) $_POST['gps_lng'] : null;
        $cropType = trim($_POST['current_crop_type'] ?? '');

        // Confirm the block actually belongs to this (already tenant-checked) farm.
        $blockStmt = db()->prepare('SELECT id FROM blocks WHERE id = :id AND farm_id = :farm_id');
        $blockStmt->execute(['id' => $blockId, 'farm_id' => $farmId]);

        if (!$blockStmt->fetch() || $plotCode === '') {
            $error = 'A valid block and plot code are required.';
        } else {
            try {
                db()->prepare(
                    'INSERT INTO plots (block_id, plot_code, size_hectares, gps_lat, gps_lng, current_crop_type)
                     VALUES (:block_id, :plot_code, :size, :lat, :lng, :crop_type)'
                )->execute([
                    'block_id' => $blockId,
                    'plot_code' => $plotCode,
                    'size' => $sizeHectares,
                    'lat' => $gpsLat,
                    'lng' => $gpsLng,
                    'crop_type' => $cropType ?: null,
                ]);
                flash('success', 'Plot added.');
                redirect('/admin/farm-view.php?id=' . $farmId);
            } catch (PDOException $e) {
                $error = 'That plot code already exists in this block.';
            }
        }
    }
}

$blocks = db()->prepare('SELECT * FROM blocks WHERE farm_id = :farm_id ORDER BY name');
$blocks->execute(['farm_id' => $farmId]);
$blocks = $blocks->fetchAll();

$plotsByBlock = [];
if ($blocks) {
    $blockIds = array_column($blocks, 'id');
    $stmt = db()->prepare('SELECT * FROM plots WHERE block_id IN (' . in_placeholders($blockIds) . ') ORDER BY plot_code');
    $stmt->execute($blockIds);
    foreach ($stmt->fetchAll() as $plot) {
        $plotsByBlock[$plot['block_id']][] = $plot;
    }
}

$pageTitle = $farm['name'];
$activePage = 'farms';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/farms.php">&larr; All farms</a></p>
<h1><?= e($farm['name']) ?> <span class="muted">(<?= e($farm['code']) ?>)</span></h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Add a block</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/farm-view.php?id=<?= $farmId ?>">
        <input type="hidden" name="action" value="add_block">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label for="description">Description</label>
        <input type="text" id="description" name="description" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Add block</button>
    </form>
</div>

<?php if (!$blocks): ?>
    <p class="muted">No blocks yet — add one above to start dividing this farm up.</p>
<?php endif; ?>

<?php foreach ($blocks as $block): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h2 style="margin-top:0;"><?= e($block['name']) ?></h2>
        <?php if ($block['description']): ?><p class="muted"><?= e($block['description']) ?></p><?php endif; ?>

        <table style="margin-bottom:1rem;">
            <thead>
                <tr>
                    <th>Plot code</th>
                    <th>Size (ha)</th>
                    <th>Current crop</th>
                    <th>GPS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plotsByBlock[$block['id']])): ?>
                    <tr><td colspan="4">No plots yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($plotsByBlock[$block['id']] ?? [] as $plot): ?>
                    <tr>
                        <td><?= e($plot['plot_code']) ?></td>
                        <td><?= $plot['size_hectares'] !== null ? e((string) $plot['size_hectares']) : '—' ?></td>
                        <td><?= e($plot['current_crop_type'] ?? '—') ?></td>
                        <td><?= $plot['gps_lat'] !== null ? e($plot['gps_lat'] . ', ' . $plot['gps_lng']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <details>
            <summary style="cursor:pointer; color:#2f5233; font-size:0.9rem;">Add a plot to this block</summary>
            <form method="POST" action="<?= BASE_URL ?>/admin/farm-view.php?id=<?= $farmId ?>" style="margin-top:0.75rem; max-width:420px;">
                <input type="hidden" name="action" value="add_plot">
                <input type="hidden" name="block_id" value="<?= (int) $block['id'] ?>">

                <label>Plot code</label>
                <input type="text" name="plot_code" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

                <label>Size (hectares)</label>
                <input type="number" step="0.01" name="size_hectares" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

                <label>Current crop type</label>
                <input type="text" name="current_crop_type" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

                <label>GPS latitude</label>
                <input type="text" name="gps_lat" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

                <label>GPS longitude</label>
                <input type="text" name="gps_lng" style="width:100%; padding:0.5rem; margin-bottom:1rem;">

                <button type="submit" class="btn">Add plot</button>
            </form>
        </details>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
