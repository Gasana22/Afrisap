<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$cropOperations = db_all(
    "SELECT co.*, cc.crop_type, cc.crop_batch_id, f.name AS farm_name
     FROM crop_operations co
     JOIN crop_cycles cc ON cc.id = co.crop_cycle_id
     JOIN farms f ON f.id = cc.farm_id
     WHERE cc.organization_id = :id AND co.activity_date = :date
     ORDER BY co.created_at DESC",
    ['id' => $orgId, 'date' => $date]
);

$attendanceRecords = db_all(
    "SELECT a.*, w.name AS worker_name, w.department
     FROM attendance a
     JOIN workers w ON w.id = a.worker_id
     WHERE w.organization_id = :id AND a.date = :date
     ORDER BY a.clock_in ASC",
    ['id' => $orgId, 'date' => $date]
);

$completedTasks = db_all(
    "SELECT t.*, w.name AS worker_name
     FROM tasks t
     JOIN workers w ON w.id = t.assigned_to
     WHERE t.organization_id = :id AND t.status IN ('completed', 'verified') AND DATE(t.completed_at) = :date
     ORDER BY t.completed_at DESC",
    ['id' => $orgId, 'date' => $date]
);

// Combine into one timeline sorted by time.
$timeline = [];
foreach ($cropOperations as $row) {
    $timeline[] = [
        'time' => $row['created_at'],
        'type' => 'Crop Operation',
        'icon' => 'bi-flower1',
        'title' => humanize($row['activity_type']) . ' — ' . humanize($row['crop_type']),
        'detail' => ($row['description'] ?: 'No notes') . ' (Farm: ' . $row['farm_name'] . ', Batch: ' . $row['crop_batch_id'] . ')',
    ];
}
foreach ($attendanceRecords as $row) {
    $timeline[] = [
        'time' => $row['clock_in'] ? $date . ' ' . $row['clock_in'] : $row['date'] . ' 00:00:00',
        'type' => 'Attendance',
        'icon' => 'bi-person-check',
        'title' => $row['worker_name'] . ' — ' . humanize($row['status']),
        'detail' => 'Clock in: ' . ($row['clock_in'] ?: '-') . ', Clock out: ' . ($row['clock_out'] ?: '-'),
    ];
}
foreach ($completedTasks as $row) {
    $timeline[] = [
        'time' => $row['completed_at'],
        'type' => 'Task Completed',
        'icon' => 'bi-check2-square',
        'title' => $row['title'],
        'detail' => 'Assigned to: ' . $row['worker_name'] . ' — ' . humanize($row['status']),
    ];
}
usort($timeline, fn ($a, $b) => strcmp((string) $b['time'], (string) $a['time']));

$exportParams = http_build_query(['report' => 'operational', 'date' => $date]);

render_header(['title' => 'Operational Report', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Operational Report</h4>
          <p class="text-muted small mb-0">Daily activity across crop operations, attendance and tasks.</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-download"></i> Export
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= base_url('org-admin/reporting/export.php?' . $exportParams . '&format=csv') ?>">CSV</a></li>
            <li><a class="dropdown-item" href="<?= base_url('org-admin/reporting/export.php?' . $exportParams . '&format=excel') ?>">Excel</a></li>
            <li><a class="dropdown-item" href="<?= base_url('org-admin/reporting/export.php?' . $exportParams . '&format=pdf') ?>">PDF</a></li>
          </ul>
        </div>
      </div>

      <div class="content-card mb-4">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Date</label>
            <input type="date" name="date" class="form-control" value="<?= e($date) ?>" onchange="this.form.submit()">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> View</button>
          </div>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <?php render_stat_card('Crop Operations', (string) count($cropOperations), null, 'bi-flower1', 'primary'); ?>
        </div>
        <div class="col-sm-4">
          <?php render_stat_card('Attendance Records', (string) count($attendanceRecords), null, 'bi-person-check', 'info'); ?>
        </div>
        <div class="col-sm-4">
          <?php render_stat_card('Tasks Completed', (string) count($completedTasks), null, 'bi-check2-square', 'secondary'); ?>
        </div>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Activity Timeline for <?= format_date($date) ?></h6></div>
        <?php if (!$timeline): ?>
          <?php render_empty_state('No activity recorded for this date.', 'bi-calendar-x'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Type</th>
                  <th>Summary</th>
                  <th>Detail</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($timeline as $item): ?>
                  <tr>
                    <td class="text-nowrap"><?= e(date('H:i', strtotime((string) $item['time']))) ?></td>
                    <td><span class="badge bg-light text-dark"><i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['type']) ?></span></td>
                    <td><?= e($item['title']) ?></td>
                    <td class="text-muted small"><?= e($item['detail']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
