<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];

/*
 * No dedicated "worker activity log" table exists: crop_operations.crop_cycle_id
 * is NOT NULL (see database/schema.sql), and a quick field note logged from the
 * worker portal usually has no crop cycle context to attach to. As a pragmatic
 * choice, entries are appended to offline_actions (action_type='activity_log',
 * status='synced') which is already an append-only, tenant/worker-scoped log.
 */

if (is_post() && csrf_verify()) {
    $activityType = clean_string($_POST['activity_type'] ?? '');
    $description = clean_string($_POST['description'] ?? '');
    $lat = $_POST['gps_latitude'] ?? '';
    $lng = $_POST['gps_longitude'] ?? '';

    $errors = validate(['activity_type' => $activityType], ['activity_type' => 'required']);

    if ($errors) {
        $GLOBALS['_page_errors'] = array_values($errors);
    } else {
        db_insert('offline_actions', [
            'organization_id' => $orgId,
            'worker_id' => $workerId,
            'action_type' => 'activity_log',
            'action_data' => json_encode([
                'activity_type' => $activityType,
                'description' => $description,
                'gps_latitude' => $lat !== '' ? $lat : null,
                'gps_longitude' => $lng !== '' ? $lng : null,
                'logged_at' => date('c'),
            ]),
            'status' => 'synced',
            'synced_at' => date('Y-m-d H:i:s'),
        ]);
        session_flash('success', 'Activity logged.');
        redirect('worker/activities.php');
    }
}

$recent = db_all(
    'SELECT * FROM offline_actions WHERE worker_id = :wid AND action_type = "activity_log" ORDER BY created_at DESC LIMIT 20',
    ['wid' => $workerId]
);

$activityTypes = ['planting', 'irrigation', 'spraying', 'weeding', 'fertilizing', 'monitoring', 'harvesting', 'other'];

render_header(['title' => 'Log Activity', 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content">
    <?php render_alerts(); ?>
    <h5 class="fw-bold mb-3">Log Field Activity</h5>

    <form method="post" class="content-card p-3 mb-3" data-offline-action="activity_log">
        <?= csrf_field() ?>
        <div class="mb-2">
            <label class="form-label small">Activity Type</label>
            <select name="activity_type" class="form-select" required>
                <option value="">Select...</option>
                <?php foreach ($activityTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= ($_POST['activity_type'] ?? '') === $type ? 'selected' : '' ?>><?= e(humanize($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label small">Notes</label>
            <textarea name="description" class="form-control" rows="3" placeholder="What did you do?"><?= e($_POST['description'] ?? '') ?></textarea>
        </div>

        <input type="hidden" id="gps_latitude" name="gps_latitude" value="">
        <input type="hidden" id="gps_longitude" name="gps_longitude" value="">
        <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-2"
            data-gps-capture data-lat-field="gps_latitude" data-lng-field="gps_longitude" data-status-field="gpsStatus">
            <i class="bi bi-geo-alt me-1"></i> Capture Location (optional)
        </button>
        <div id="gpsStatus" class="small text-muted mb-2"></div>

        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> Log Activity</button>
    </form>

    <h6 class="fw-bold mb-2">Recent Entries</h6>
    <?php if (!$recent): ?>
        <?php render_empty_state('No activity logged yet.', 'bi-journal-text'); ?>
    <?php else: ?>
        <?php foreach ($recent as $entry): ?>
            <?php $data = json_decode($entry['action_data'], true) ?: []; ?>
            <div class="task-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold"><?= e(humanize($data['activity_type'] ?? 'Activity')) ?></div>
                        <?php if (!empty($data['description'])): ?>
                            <div class="small text-muted"><?= e($data['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted"><?= e(format_date($entry['created_at'], 'd M, g:i A')) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker', 'js' => ['gps', 'offline']]); ?>
<script>
document.querySelectorAll('form[data-offline-action]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!navigator.onLine) {
            e.preventDefault();
            const fields = Object.fromEntries(new FormData(form).entries());
            SFPOffline.enqueue('activity_log', {
                activity_type: fields.activity_type || '',
                description: fields.description || '',
                gps_latitude: fields.gps_latitude || null,
                gps_longitude: fields.gps_longitude || null,
                logged_at: new Date().toISOString(),
            });
            alert('You are offline. This entry will sync automatically once you reconnect.');
        }
    });
});
</script>
