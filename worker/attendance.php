<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];
$today = date('Y-m-d');

$row = db_one('SELECT * FROM attendance WHERE worker_id = :wid AND date = :d', ['wid' => $workerId, 'd' => $today]);

if (is_post() && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'clock_in' && !$row) {
        db_insert('attendance', [
            'worker_id' => $workerId,
            'date' => $today,
            'clock_in' => date('H:i:s'),
            'status' => 'present',
            'gps_latitude_in' => ($_POST['gps_latitude_in'] ?? '') !== '' ? $_POST['gps_latitude_in'] : null,
            'gps_longitude_in' => ($_POST['gps_longitude_in'] ?? '') !== '' ? $_POST['gps_longitude_in'] : null,
        ]);
        audit_log($orgId, null, 'create', 'attendance', null, null, ['worker_id' => $workerId, 'date' => $today, 'clock_in' => date('H:i:s')]);
        session_flash('success', 'Clocked in at ' . date('g:i A') . '.');
        redirect('worker/attendance.php');
    } elseif ($action === 'clock_out' && $row && $row['clock_in'] && !$row['clock_out']) {
        db_update('attendance', [
            'clock_out' => date('H:i:s'),
            'gps_latitude_out' => ($_POST['gps_latitude_out'] ?? '') !== '' ? $_POST['gps_latitude_out'] : null,
            'gps_longitude_out' => ($_POST['gps_longitude_out'] ?? '') !== '' ? $_POST['gps_longitude_out'] : null,
        ], 'id = :id', ['id' => $row['id']]);
        audit_log($orgId, null, 'update', 'attendance', $row['id'], null, ['clock_out' => date('H:i:s')]);
        session_flash('success', 'Clocked out at ' . date('g:i A') . '. Have a good day!');
        redirect('worker/attendance.php');
    } else {
        $GLOBALS['_page_errors'] = ['That action is not available right now.'];
    }
}

$history = db_all(
    'SELECT * FROM attendance WHERE worker_id = :wid AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY date DESC',
    ['wid' => $workerId]
);

render_header(['title' => 'Attendance', 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content">
    <?php render_alerts(); ?>
    <h5 class="fw-bold mb-3">Attendance</h5>

    <div class="content-card p-3 mb-3">
        <?php if (!$row): ?>
            <p class="text-muted small">You have not clocked in today.</p>
            <form method="post" data-offline-action="clock_in">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clock_in">
                <input type="hidden" id="gps_latitude_in" name="gps_latitude_in" value="">
                <input type="hidden" id="gps_longitude_in" name="gps_longitude_in" value="">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-2"
                    data-gps-capture data-lat-field="gps_latitude_in" data-lng-field="gps_longitude_in" data-status-field="gpsInStatus">
                    <i class="bi bi-geo-alt me-1"></i> Capture Location
                </button>
                <div id="gpsInStatus" class="small text-muted mb-2"></div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Clock In</button>
            </form>
        <?php elseif ($row['clock_in'] && !$row['clock_out']): ?>
            <p class="text-muted small">Clocked in at <?= e(date('g:i A', strtotime($row['clock_in']))) ?>.</p>
            <form method="post" data-offline-action="clock_out">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clock_out">
                <input type="hidden" id="gps_latitude_out" name="gps_latitude_out" value="">
                <input type="hidden" id="gps_longitude_out" name="gps_longitude_out" value="">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-2"
                    data-gps-capture data-lat-field="gps_latitude_out" data-lng-field="gps_longitude_out" data-status-field="gpsOutStatus">
                    <i class="bi bi-geo-alt me-1"></i> Capture Location
                </button>
                <div id="gpsOutStatus" class="small text-muted mb-2"></div>
                <button type="submit" class="btn btn-danger w-100"><i class="bi bi-box-arrow-right me-1"></i> Clock Out</button>
            </form>
        <?php else: ?>
            <?php
            $seconds = strtotime($row['date'] . ' ' . $row['clock_out']) - strtotime($row['date'] . ' ' . $row['clock_in']);
            $hours = round(max(0, $seconds) / 3600, 1);
            ?>
            <div class="text-center">
                <i class="bi bi-check-circle text-success display-6 d-block mb-2"></i>
                <p class="mb-1">Clocked in at <?= e(date('g:i A', strtotime($row['clock_in']))) ?>, out at <?= e(date('g:i A', strtotime($row['clock_out']))) ?>.</p>
                <p class="fw-bold mb-0"><?= e($hours) ?> hours worked today</p>
            </div>
        <?php endif; ?>
    </div>

    <h6 class="fw-bold mb-2">Last 7 Days</h6>
    <div class="content-card p-3">
        <?php if (!$history): ?>
            <?php render_empty_state('No attendance records yet.', 'bi-calendar-x'); ?>
        <?php else: ?>
            <table class="table table-sm mb-0">
                <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= e(format_date($h['date'], 'd M')) ?></td>
                        <td><?= $h['clock_in'] ? e(date('g:i A', strtotime($h['clock_in']))) : '-' ?></td>
                        <td><?= $h['clock_out'] ? e(date('g:i A', strtotime($h['clock_out']))) : '-' ?></td>
                        <td><?php render_status_badge($h['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker', 'js' => ['gps', 'offline']]); ?>
<script>
// Queue clock in/out while offline instead of letting the form fail; offline.js
// (already loaded above) flushes the queue to /api/v1/sync.php once back online.
document.querySelectorAll('form[data-offline-action]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!navigator.onLine) {
            e.preventDefault();
            const action = form.dataset.offlineAction;
            const fields = Object.fromEntries(new FormData(form).entries());
            const actionData = { date: '<?= e($today) ?>' };
            if (action === 'clock_in') {
                actionData.gps_latitude_in = fields.gps_latitude_in || null;
                actionData.gps_longitude_in = fields.gps_longitude_in || null;
            } else if (action === 'clock_out') {
                actionData.gps_latitude_out = fields.gps_latitude_out || null;
                actionData.gps_longitude_out = fields.gps_longitude_out || null;
            }
            SFPOffline.enqueue(action, actionData);
            alert('You are offline. This will sync automatically once you reconnect.');
        }
    });
});
</script>
