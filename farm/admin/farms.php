<?php
require_once __DIR__ . '/includes/auth-check.php';
require_tenant_user();

$error = flash('error');
$success = flash('success');

// --- Handle "add farm" submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('farms.manage');

    $name = trim($_POST['name'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $sizeHectares = ($_POST['size_hectares'] ?? '') !== '' ? (float) $_POST['size_hectares'] : null;
    $organizationId = current_organization_id();

    if ($name === '' || !$organizationId) {
        $error = 'Farm name and organization are required.';
    } else {
        $stmt = db()->prepare(
            'INSERT INTO farms (name, size_hectares, district, village, organization_id, owner_id, status)
             VALUES (:name, :size, :district, :village, :org_id, :owner_id, "active")'
        );
        $stmt->execute([
            'name' => $name,
            'size' => $sizeHectares,
            'district' => $district ?: null,
            'village' => $village ?: null,
            'org_id' => $organizationId,
            'owner_id' => current_user()['id'],
        ]);

        // Backfill the human-readable farm code, same pattern as migration 004.
        $newId = (int) db()->lastInsertId();
        db()->prepare('UPDATE farms SET code = :code WHERE id = :id')
            ->execute(['code' => 'FARM' . str_pad((string) $newId, 2, '0', STR_PAD_LEFT), 'id' => $newId]);

        audit_log('create', 'farms', (string) $newId, null, ['name' => $name, 'organization_id' => $organizationId]);

        flash('success', 'Farm created.');
        redirect('/admin/farms.php');
    }
}

$stmt = db()->prepare(
    'SELECT f.*, o.name AS organization_name FROM farms f
     LEFT JOIN organizations o ON o.id = f.organization_id
     WHERE f.organization_id = :org_id
     ORDER BY f.created_at DESC'
);
$stmt->execute(['org_id' => current_organization_id()]);
$farms = $stmt->fetchAll();

$pageTitle = 'Farms';
$activePage = 'farms';
require __DIR__ . '/includes/header.php';
?>

<h1>Farms</h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:480px;">
    <h2 style="margin-top:0; font-size:1rem;">Add a farm</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/farms.php">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

        <label for="district">District</label>
        <input type="text" id="district" name="district" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

        <label for="village">Village</label>
        <input type="text" id="village" name="village" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">

        <label for="size_hectares">Size (hectares)</label>
        <input type="number" step="0.01" id="size_hectares" name="size_hectares" style="width:100%; padding:0.5rem; margin-bottom:1rem;">

        <button type="submit" class="btn">Add farm</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>District</th>
            <th>Size (ha)</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$farms): ?>
            <tr><td colspan="6">No farms yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($farms as $farm): ?>
            <tr>
                <td><?= e($farm['code']) ?></td>
                <td><?= e($farm['name']) ?></td>
                <td><?= e($farm['district']) ?></td>
                <td><?= $farm['size_hectares'] !== null ? e((string) $farm['size_hectares']) : '—' ?></td>
                <td><?= e($farm['status']) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/farm-view.php?id=<?= (int) $farm['id'] ?>">Manage</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
