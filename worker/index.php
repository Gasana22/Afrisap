<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];
$today = date('Y-m-d');

$attendanceToday = db_one('SELECT * FROM attendance WHERE worker_id = :wid AND date = :d', ['wid' => $workerId, 'd' => $today]);

$attendanceLabel = 'Not Clocked In';
$attendanceIcon = 'bi-clock';
$attendanceAccent = 'secondary';
$hoursToday = null;

if ($attendanceToday) {
    if ($attendanceToday['clock_in'] && $attendanceToday['clock_out']) {
        $attendanceLabel = 'Clocked Out';
        $attendanceAccent = 'info';
        $seconds = strtotime($today . ' ' . $attendanceToday['clock_out']) - strtotime($today . ' ' . $attendanceToday['clock_in']);
        $hoursToday = round(max(0, $seconds) / 3600, 1);
    } elseif ($attendanceToday['clock_in']) {
        $attendanceLabel = 'Clocked In';
        $attendanceAccent = 'primary';
    }
}

$activeTaskCount = (int) db_value(
    'SELECT COUNT(*) FROM tasks WHERE assigned_to = :wid AND organization_id = :org AND status IN ("pending","in_progress")',
    ['wid' => $workerId, 'org' => $orgId]
);

$todayTasks = db_all(
    'SELECT t.*, f.name AS farm_name, b.name AS block_name
     FROM tasks t
     LEFT JOIN farms f ON f.id = t.farm_id
     LEFT JOIN blocks b ON b.id = t.block_id
     WHERE t.assigned_to = :wid AND t.organization_id = :org AND t.status IN ("pending","in_progress")
     ORDER BY (t.deadline IS NULL), t.deadline ASC, FIELD(t.priority,"urgent","high","medium","low")
     LIMIT 5',
    ['wid' => $workerId, 'org' => $orgId]
);

$recentCompleted = db_all(
    'SELECT * FROM tasks WHERE assigned_to = :wid AND organization_id = :org AND status IN ("completed","verified")
     ORDER BY completed_at DESC LIMIT 3',
    ['wid' => $workerId, 'org' => $orgId]
);

render_header(['title' => 'Dashboard', 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content">
    <?php render_alerts(); ?>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <?php render_stat_card('Active Tasks', (string) $activeTaskCount, null, 'bi-list-check', 'primary'); ?>
        </div>
        <div class="col-6">
            <?php render_stat_card(
                $hoursToday !== null ? 'Hours Today' : 'Attendance',
                $hoursToday !== null ? $hoursToday . 'h' : $attendanceLabel,
                null,
                $attendanceIcon,
                $attendanceAccent
            ); ?>
        </div>
    </div>

    <div class="content-card mb-3 p-3">
        <?php if (!$attendanceToday): ?>
            <a href="<?= base_url('worker/attendance.php') ?>" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Clock In</a>
        <?php elseif ($attendanceToday['clock_in'] && !$attendanceToday['clock_out']): ?>
            <a href="<?= base_url('worker/attendance.php') ?>" class="btn btn-danger w-100"><i class="bi bi-box-arrow-right me-1"></i> Clock Out</a>
        <?php else: ?>
            <div class="text-center text-muted"><i class="bi bi-check-circle text-success me-1"></i> Attendance recorded for today (<?= e((string) $hoursToday) ?>h worked)</div>
        <?php endif; ?>
    </div>

    <h6 class="fw-bold mb-2">Today's Tasks</h6>
    <?php if (!$todayTasks): ?>
        <?php render_empty_state('No pending tasks right now.', 'bi-check2-circle'); ?>
    <?php else: ?>
        <?php foreach ($todayTasks as $task): ?>
            <?php $location = trim(($task['farm_name'] ?? '') . ($task['block_name'] ? ' / ' . $task['block_name'] : '')); ?>
            <div class="task-card priority-<?= e($task['priority']) ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold"><?= e($task['title']) ?></div>
                        <div class="small text-muted"><?= e(humanize($task['task_type'])) ?><?= $location ? ' · ' . e($location) : '' ?></div>
                        <?php if ($task['deadline']): ?>
                            <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= e(format_date($task['deadline'])) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php render_status_badge($task['status']); ?>
                </div>
                <a href="<?= base_url('worker/task.php?id=' . $task['id']) ?>" class="btn btn-sm btn-outline-primary mt-2">Start Task</a>
            </div>
        <?php endforeach; ?>
        <a href="<?= base_url('worker/tasks.php') ?>" class="d-block text-center small mb-3">View all tasks &rarr;</a>
    <?php endif; ?>

    <h6 class="fw-bold mb-2">Recent Activity</h6>
    <?php if (!$recentCompleted): ?>
        <?php render_empty_state('No completed tasks yet.', 'bi-clock-history'); ?>
    <?php else: ?>
        <ul class="list-unstyled">
            <?php foreach ($recentCompleted as $task): ?>
                <li class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <span><?= e($task['title']) ?></span>
                    <span class="small text-muted ms-auto"><?= e(format_date($task['completed_at'], 'd M')) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker']); ?>
