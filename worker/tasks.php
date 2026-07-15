<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];

$statusFilter = (string) ($_GET['status'] ?? '');
$allowedStatuses = ['pending', 'in_progress', 'completed', 'verified', 'canceled'];

$sql = 'SELECT t.*, f.name AS farm_name, b.name AS block_name
        FROM tasks t
        LEFT JOIN farms f ON f.id = t.farm_id
        LEFT JOIN blocks b ON b.id = t.block_id
        WHERE t.assigned_to = :wid AND t.organization_id = :org';
$params = ['wid' => $workerId, 'org' => $orgId];

if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' AND t.status = :status';
    $params['status'] = $statusFilter;
}

$sql .= ' ORDER BY (t.deadline IS NULL), t.deadline ASC, FIELD(t.priority,"urgent","high","medium","low")';

$tasks = db_all($sql, $params);

render_header(['title' => 'My Tasks', 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content">
    <?php render_alerts(); ?>
    <h5 class="fw-bold mb-3">My Tasks</h5>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="<?= base_url('worker/tasks.php') ?>" class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
        <a href="<?= base_url('worker/tasks.php?status=pending') ?>" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pending</a>
        <a href="<?= base_url('worker/tasks.php?status=in_progress') ?>" class="btn btn-sm <?= $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' ?>">In Progress</a>
        <a href="<?= base_url('worker/tasks.php?status=completed') ?>" class="btn btn-sm <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline-secondary' ?>">Completed</a>
    </div>

    <?php if (!$tasks): ?>
        <?php render_empty_state('No tasks found for this filter.', 'bi-list-check'); ?>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <?php $location = trim(($task['farm_name'] ?? '') . ($task['block_name'] ? ' / ' . $task['block_name'] : '')); ?>
            <a href="<?= base_url('worker/task.php?id=' . $task['id']) ?>" class="text-decoration-none text-reset">
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
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker']); ?>
