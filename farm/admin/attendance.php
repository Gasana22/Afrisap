<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

$farms = [];
if ($farmIds) {
    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();
}

$farmId = (int) ($_GET['farm_id'] ?? $_POST['farm_id'] ?? ($farms[0]['id'] ?? 0));
$date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');

if ($farmId && !in_array($farmId, $farmIds, true)) {
    http_response_code(404);
    exit('404 — farm not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('workers.manage');

    foreach ($_POST['status'] ?? [] as $workerId => $status) {
        $workerId = (int) $workerId;
        if (!in_array($status, ['present', 'absent', 'late', 'half_day'], true)) {
            continue;
        }
        // Confirm the worker belongs to a farm this user can see before writing.
        $check = db()->prepare('SELECT id FROM workers WHERE id = :id AND farm_id = :farm_id');
        $check->execute(['id' => $workerId, 'farm_id' => $farmId]);
        if (!$check->fetch()) {
            continue;
        }

        db()->prepare(
            'INSERT INTO worker_attendance (worker_id, attendance_date, status, approved_by, approved_at)
             VALUES (:worker, :date, :status, :approved_by, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), approved_by = VALUES(approved_by), approved_at = NOW()'
        )->execute([
            'worker' => $workerId,
            'date' => $date,
            'status' => $status,
            'approved_by' => current_user()['id'],
        ]);
    }

    flash('success', 'Attendance saved for ' . $date . '.');
    redirect('/admin/attendance.php?farm_id=' . $farmId . '&date=' . $date);
}

$workers = [];
$existing = [];
if ($farmId) {
    $workerStmt = db()->prepare('SELECT * FROM workers WHERE farm_id = :farm_id AND status = "active" ORDER BY name');
    $workerStmt->execute(['farm_id' => $farmId]);
    $workers = $workerStmt->fetchAll();

    if ($workers) {
        $workerIds = array_column($workers, 'id');
        $existingStmt = db()->prepare(
            'SELECT worker_id, status FROM worker_attendance WHERE attendance_date = ? AND worker_id IN (' . in_placeholders($workerIds) . ')'
        );
        $existingStmt->execute(array_merge([$date], $workerIds));
        foreach ($existingStmt->fetchAll() as $row) {
            $existing[$row['worker_id']] = $row['status'];
        }
    }
}

$pageTitle = 'Attendance';
$activePage = 'workers';
require __DIR__ . '/includes/header.php';
?>

<h1>Daily Attendance</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if (!$farms): ?>
    <div class="alert alert-error">No farms available yet.</div>
<?php else: ?>

<form method="GET" action="<?= BASE_URL ?>/admin/attendance.php" style="display:flex; gap:1rem; align-items:flex-end; margin-bottom:1.5rem;">
    <div>
        <label>Farm</label>
        <select name="farm_id" onchange="this.form.submit()" style="padding:0.5rem;">
            <?php foreach ($farms as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= $farmId === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Date</label>
        <input type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()" style="padding:0.5rem;">
    </div>
</form>

<form method="POST" action="<?= BASE_URL ?>/admin/attendance.php">
    <input type="hidden" name="farm_id" value="<?= $farmId ?>">
    <input type="hidden" name="date" value="<?= e($date) ?>">
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Worker</th><th>Role</th><th>Status</th></tr></thead>
        <tbody>
            <?php if (!$workers): ?><tr><td colspan="3">No active workers on this farm.</td></tr><?php endif; ?>
            <?php foreach ($workers as $w): ?>
                <?php $current = $existing[$w['id']] ?? 'present'; ?>
                <tr>
                    <td><?= e($w['name']) ?></td>
                    <td><?= e($w['role_title'] ?? '—') ?></td>
                    <td>
                        <select name="status[<?= (int) $w['id'] ?>]" style="padding:0.4rem;">
                            <?php foreach (['present', 'late', 'half_day', 'absent'] as $s): ?>
                                <option value="<?= $s ?>" <?= $current === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($workers): ?><button type="submit" class="btn">Save attendance</button><?php endif; ?>
</form>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
