<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('workers.manage');

    $farmId = (int) ($_POST['farm_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roleTitle = trim($_POST['role_title'] ?? '');
    $hireDate = ($_POST['hire_date'] ?? '') ?: null;
    $payRate = ($_POST['pay_rate'] ?? '') !== '' ? (float) $_POST['pay_rate'] : null;
    $payRateType = $_POST['pay_rate_type'] ?? 'daily';

    if (!in_array($farmId, $farmIds, true) || $name === '') {
        $error = 'A valid farm and name are required.';
    } else {
        db()->prepare(
            'INSERT INTO workers (farm_id, name, phone, role_title, hire_date, pay_rate, pay_rate_type, status)
             VALUES (:farm_id, :name, :phone, :role, :hire, :rate, :rate_type, "active")'
        )->execute([
            'farm_id' => $farmId,
            'name' => $name,
            'phone' => $phone ?: null,
            'role' => $roleTitle ?: null,
            'hire' => $hireDate,
            'rate' => $payRate,
            'rate_type' => $payRateType,
        ]);
        flash('success', 'Worker added.');
        redirect('/admin/workers.php');
    }
}

$workers = [];
$farms = [];
if ($farmIds) {
    $stmt = db()->prepare(
        'SELECT w.*, f.name AS farm_name FROM workers w JOIN farms f ON f.id = w.farm_id
         WHERE w.farm_id IN (' . in_placeholders($farmIds) . ') ORDER BY w.created_at DESC'
    );
    $stmt->execute($farmIds);
    $workers = $stmt->fetchAll();

    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();
}

$pageTitle = 'Workers';
$activePage = 'workers';
require __DIR__ . '/includes/header.php';
?>

<h1>Workers</h1>
<p><a href="<?= BASE_URL ?>/admin/attendance.php">Go to daily attendance &rarr;</a></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if (!$farms): ?>
    <div class="alert alert-error">No farms available yet. <a href="<?= BASE_URL ?>/admin/farms.php">Add a farm</a> first.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Add a worker</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/workers.php">
        <label>Farm</label>
        <select name="farm_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($farms as $f): ?>
                <option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Name</label>
        <input type="text" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Phone</label>
        <input type="text" name="phone" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Role / title</label>
        <input type="text" name="role_title" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Hire date</label>
        <input type="date" name="hire_date" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Pay rate</label>
        <input type="number" step="0.01" name="pay_rate" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Pay rate type</label>
        <select name="pay_rate_type" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <option value="daily">Daily</option><option value="monthly">Monthly</option>
        </select>
        <button type="submit" class="btn">Add</button>
    </form>
</div>

<table>
    <thead><tr><th>Name</th><th>Farm</th><th>Role</th><th>Pay rate</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php if (!$workers): ?><tr><td colspan="6">No workers yet.</td></tr><?php endif; ?>
        <?php foreach ($workers as $w): ?>
            <tr>
                <td><?= e($w['name']) ?></td>
                <td><?= e($w['farm_name']) ?></td>
                <td><?= e($w['role_title'] ?? '—') ?></td>
                <td><?= e(($w['pay_rate'] ?? '—') . ' / ' . $w['pay_rate_type']) ?></td>
                <td><?= e($w['status']) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/worker-view.php?id=<?= (int) $w['id'] ?>">Manage</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
