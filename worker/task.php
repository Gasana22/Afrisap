<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];

$taskId = (int) ($_GET['id'] ?? 0);

$task = db_one(
    'SELECT t.*, f.name AS farm_name, b.name AS block_name
     FROM tasks t
     LEFT JOIN farms f ON f.id = t.farm_id
     LEFT JOIN blocks b ON b.id = t.block_id
     WHERE t.id = :id AND t.organization_id = :org AND t.assigned_to = :wid',
    ['id' => $taskId, 'org' => $orgId, 'wid' => $workerId]
);

if (!$task) {
    session_flash('error', 'Task not found or not assigned to you.');
    redirect('worker/tasks.php');
}

if (is_post() && csrf_verify()) {
    $newStatus = (string) ($_POST['new_status'] ?? '');
    $allowedTransitions = [
        'pending' => 'in_progress',
        'in_progress' => 'completed',
    ];

    if (isset($allowedTransitions[$task['status']]) && $allowedTransitions[$task['status']] === $newStatus) {
        $data = ['status' => $newStatus];

        if ($newStatus === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');

            $lat = $_POST['gps_latitude'] ?? '';
            $lng = $_POST['gps_longitude'] ?? '';
            if ($lat !== '' && $lng !== '') {
                $data['gps_latitude'] = $lat;
                $data['gps_longitude'] = $lng;
            }

            if (!empty($_FILES['photo']['name'])) {
                try {
                    $path = handle_upload($_FILES['photo'], $orgId, 'tasks');
                    if ($path) {
                        $existingPhotos = json_decode($task['verification_photos'] ?? '[]', true) ?: [];
                        $existingPhotos[] = $path;
                        $data['verification_photos'] = json_encode($existingPhotos);
                    }
                } catch (RuntimeException $e) {
                    $GLOBALS['_page_errors'] = [$e->getMessage()];
                }
            }
        }

        if (empty($GLOBALS['_page_errors'])) {
            tenant_update('tasks', $orgId, $taskId, $data);
            audit_log($orgId, null, 'update', 'tasks', $taskId, ['status' => $task['status']], $data);
            session_flash('success', 'Task updated to ' . humanize($newStatus) . '.');
            redirect('worker/task.php?id=' . $taskId);
        }
    } else {
        $GLOBALS['_page_errors'] = ['That status change is not allowed.'];
    }
}

$location = trim(($task['farm_name'] ?? '') . ($task['block_name'] ? ' / ' . $task['block_name'] : ''));
$photos = json_decode($task['verification_photos'] ?? '[]', true) ?: [];

render_header(['title' => $task['title'], 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content">
    <?php render_alerts(); ?>

    <a href="<?= base_url('worker/tasks.php') ?>" class="small d-inline-block mb-2"><i class="bi bi-arrow-left"></i> Back to tasks</a>

    <div class="task-card priority-<?= e($task['priority']) ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-0"><?= e($task['title']) ?></h5>
            <?php render_status_badge($task['status']); ?>
        </div>
        <p class="text-muted mb-2"><?= nl2br(e($task['description'] ?? '')) ?></p>
        <ul class="list-unstyled small mb-0">
            <li><i class="bi bi-tag me-1"></i> <?= e(humanize($task['task_type'])) ?></li>
            <?php if ($location): ?><li><i class="bi bi-geo-alt me-1"></i> <?= e($location) ?></li><?php endif; ?>
            <?php if ($task['deadline']): ?><li><i class="bi bi-calendar3 me-1"></i> Due <?= e(format_date($task['deadline'])) ?></li><?php endif; ?>
            <li><i class="bi bi-flag me-1"></i> Priority: <?= e(humanize($task['priority'])) ?></li>
        </ul>
    </div>

    <?php if ($photos): ?>
        <div class="content-card p-3 mb-3">
            <h6 class="fw-bold">Verification Photos</h6>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($photos as $photo): ?>
                    <img src="<?= e(uploaded_file_url($photo)) ?>" alt="Task photo" style="width:90px;height:90px;object-fit:cover;border-radius:8px;">
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($task['status'] === 'pending'): ?>
        <form method="post" class="content-card p-3 mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="new_status" value="in_progress">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-play-fill me-1"></i> Start Task</button>
        </form>
    <?php elseif ($task['status'] === 'in_progress'): ?>
        <form method="post" enctype="multipart/form-data" class="content-card p-3 mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="new_status" value="completed">
            <input type="hidden" id="gps_latitude" name="gps_latitude" value="">
            <input type="hidden" id="gps_longitude" name="gps_longitude" value="">

            <div class="mb-2">
                <label class="form-label small">Completion photo (optional)</label>
                <input type="file" name="photo" accept="image/*" class="form-control form-control-sm">
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-2"
                data-gps-capture data-lat-field="gps_latitude" data-lng-field="gps_longitude" data-status-field="gpsStatus">
                <i class="bi bi-geo-alt me-1"></i> Capture Location
            </button>
            <div id="gpsStatus" class="small text-muted mb-2"></div>

            <button type="submit" class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i> Mark Completed</button>
        </form>
    <?php else: ?>
        <div class="content-card p-3 mb-3 text-center text-muted">
            <i class="bi bi-check-circle text-success me-1"></i> This task is <?= e(humanize($task['status'])) ?><?= $task['completed_at'] ? ' (completed ' . e(format_date($task['completed_at'])) . ')' : '' ?>.
        </div>
    <?php endif; ?>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker', 'js' => ['gps']]); ?>
