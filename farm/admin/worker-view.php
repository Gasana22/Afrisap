<?php
require_once __DIR__ . '/includes/auth-check.php';

$workerId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT w.*, f.name AS farm_name, f.organization_id FROM workers w JOIN farms f ON f.id = w.farm_id WHERE w.id = :id'
);
$stmt->execute(['id' => $workerId]);
$worker = $stmt->fetch();

if (!$worker || (!is_platform_user() && (int) $worker['organization_id'] !== (int) current_organization_id())) {
    http_response_code(404);
    exit('404 — worker not found.');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('workers.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_task') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $error = 'Task title is required.';
        } else {
            db()->prepare(
                'INSERT INTO worker_tasks (worker_id, assigned_by, title, description, due_date, status)
                 VALUES (:worker, :by, :title, :description, :due, "pending")'
            )->execute([
                'worker' => $workerId,
                'by' => current_user()['id'],
                'title' => $title,
                'description' => trim($_POST['description'] ?? '') ?: null,
                'due' => $_POST['due_date'] ?: null,
            ]);
            if ($worker['user_id']) {
                notify((int) $worker['user_id'], 'New task assigned', $title, 'info');
            }
            flash('success', 'Task assigned.');
            redirect('/admin/worker-view.php?id=' . $workerId);
        }
    }

    if ($action === 'update_task_status') {
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $taskCheck = db()->prepare('SELECT id FROM worker_tasks WHERE id = :id AND worker_id = :worker');
        $taskCheck->execute(['id' => $taskId, 'worker' => $workerId]);
        if ($taskCheck->fetch() && in_array($status, ['pending', 'ongoing', 'completed', 'verified'], true)) {
            $verifiedFields = $status === 'verified'
                ? ['verified_by' => current_user()['id'], 'verified_at' => date('Y-m-d H:i:s')]
                : ['verified_by' => null, 'verified_at' => null];
            db()->prepare(
                'UPDATE worker_tasks SET status = :status, verified_by = :verified_by, verified_at = :verified_at WHERE id = :id'
            )->execute(array_merge(['status' => $status, 'id' => $taskId], $verifiedFields));
            flash('success', 'Task updated.');
        }
        redirect('/admin/worker-view.php?id=' . $workerId);
    }

    if ($action === 'add_payroll') {
        $basePay = (float) ($_POST['base_pay'] ?? 0);
        $bonuses = (float) ($_POST['bonuses'] ?? 0);
        $deductions = (float) ($_POST['deductions'] ?? 0);
        $periodStart = $_POST['period_start'] ?? '';
        $periodEnd = $_POST['period_end'] ?? '';

        if ($periodStart === '' || $periodEnd === '') {
            $error = 'Pay period start and end are required.';
        } else {
            db()->prepare(
                'INSERT INTO worker_payroll (worker_id, period_start, period_end, base_pay, bonuses, deductions, net_pay, status, generated_by)
                 VALUES (:worker, :start, :end, :base, :bonus, :deduct, :net, "draft", :by)'
            )->execute([
                'worker' => $workerId,
                'start' => $periodStart,
                'end' => $periodEnd,
                'base' => $basePay,
                'bonus' => $bonuses,
                'deduct' => $deductions,
                'net' => $basePay + $bonuses - $deductions,
                'by' => current_user()['id'],
            ]);
            flash('success', 'Payroll draft created.');
            redirect('/admin/worker-view.php?id=' . $workerId);
        }
    }

    if ($action === 'advance_payroll_status') {
        $payrollId = (int) ($_POST['payroll_id'] ?? 0);
        $next = $_POST['next_status'] ?? '';
        $check = db()->prepare('SELECT status FROM worker_payroll WHERE id = :id AND worker_id = :worker');
        $check->execute(['id' => $payrollId, 'worker' => $workerId]);
        $current = $check->fetchColumn();

        $allowed = ['draft' => 'approved', 'approved' => 'paid'];
        if ($current && ($allowed[$current] ?? null) === $next) {
            if ($next === 'paid') {
                db()->prepare('UPDATE worker_payroll SET status = :status, paid_at = NOW() WHERE id = :id')
                    ->execute(['status' => $next, 'id' => $payrollId]);
            } else {
                db()->prepare('UPDATE worker_payroll SET status = :status WHERE id = :id')
                    ->execute(['status' => $next, 'id' => $payrollId]);
            }
            flash('success', 'Payroll status updated.');
        }
        redirect('/admin/worker-view.php?id=' . $workerId);
    }
}

$tasksStmt = db()->prepare('SELECT * FROM worker_tasks WHERE worker_id = :worker ORDER BY created_at DESC');
$tasksStmt->execute(['worker' => $workerId]);
$tasks = $tasksStmt->fetchAll();

$payrollStmt = db()->prepare('SELECT * FROM worker_payroll WHERE worker_id = :worker ORDER BY period_start DESC');
$payrollStmt->execute(['worker' => $workerId]);
$payrolls = $payrollStmt->fetchAll();

$attendanceStmt = db()->prepare('SELECT * FROM worker_attendance WHERE worker_id = :worker ORDER BY attendance_date DESC LIMIT 14');
$attendanceStmt->execute(['worker' => $workerId]);
$attendance = $attendanceStmt->fetchAll();

$pageTitle = $worker['name'];
$activePage = 'workers';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/workers.php">&larr; All workers</a></p>
<h1><?= e($worker['name']) ?></h1>
<p class="muted"><?= e($worker['role_title'] ?? 'Worker') ?> · <?= e($worker['farm_name']) ?> · Status: <?= e($worker['status']) ?></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Recent attendance</h2>
    <table>
        <thead><tr><th>Date</th><th>Status</th><th>Check-in</th><th>Check-out</th></tr></thead>
        <tbody>
            <?php if (!$attendance): ?><tr><td colspan="4">No attendance recorded yet — use <a href="<?= BASE_URL ?>/admin/attendance.php">daily attendance</a>.</td></tr><?php endif; ?>
            <?php foreach ($attendance as $row): ?>
                <tr><td><?= e($row['attendance_date']) ?></td><td><?= e($row['status']) ?></td><td><?= e($row['check_in_time'] ?? '—') ?></td><td><?= e($row['check_out_time'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Tasks</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Title</th><th>Due</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php if (!$tasks): ?><tr><td colspan="4">No tasks yet.</td></tr><?php endif; ?>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?= e($t['title']) ?></td>
                    <td><?= e($t['due_date'] ?? '—') ?></td>
                    <td><?= e($t['status']) ?></td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>/admin/worker-view.php?id=<?= $workerId ?>" style="display:flex; gap:0.4rem;">
                            <input type="hidden" name="action" value="update_task_status">
                            <input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
                            <select name="status" style="padding:0.3rem;">
                                <?php foreach (['pending', 'ongoing', 'completed', 'verified'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn" style="padding:0.3rem 0.6rem; font-size:0.8rem;">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Assign a task</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/worker-view.php?id=<?= $workerId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_task">
            <label>Title</label>
            <input type="text" name="title" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Description</label>
            <input type="text" name="description" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Due date</label>
            <input type="date" name="due_date" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Assign</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Payroll</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Period</th><th>Base</th><th>Bonuses</th><th>Deductions</th><th>Net</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php if (!$payrolls): ?><tr><td colspan="7">No payroll records yet.</td></tr><?php endif; ?>
            <?php foreach ($payrolls as $p): ?>
                <tr>
                    <td><?= e($p['period_start'] . ' – ' . $p['period_end']) ?></td>
                    <td><?= e($p['base_pay']) ?></td>
                    <td><?= e($p['bonuses']) ?></td>
                    <td><?= e($p['deductions']) ?></td>
                    <td><?= e($p['net_pay']) ?></td>
                    <td><?= e($p['status']) ?></td>
                    <td>
                        <?php if ($p['status'] === 'draft'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/admin/worker-view.php?id=<?= $workerId ?>">
                                <input type="hidden" name="action" value="advance_payroll_status">
                                <input type="hidden" name="payroll_id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="next_status" value="approved">
                                <button type="submit" class="btn" style="padding:0.3rem 0.6rem; font-size:0.8rem;">Approve</button>
                            </form>
                        <?php elseif ($p['status'] === 'approved'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/admin/worker-view.php?id=<?= $workerId ?>">
                                <input type="hidden" name="action" value="advance_payroll_status">
                                <input type="hidden" name="payroll_id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="next_status" value="paid">
                                <button type="submit" class="btn" style="padding:0.3rem 0.6rem; font-size:0.8rem;">Mark paid</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Create a payroll draft</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/worker-view.php?id=<?= $workerId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_payroll">
            <label>Period start</label>
            <input type="date" name="period_start" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Period end</label>
            <input type="date" name="period_end" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Base pay</label>
            <input type="number" step="0.01" name="base_pay" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Bonuses</label>
            <input type="number" step="0.01" name="bonuses" value="0" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Deductions</label>
            <input type="number" step="0.01" name="deductions" value="0" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Create draft</button>
        </form>
    </details>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
