<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$workerId = (int) clean_int($_GET['id'] ?? 0);
$worker = tenant_find('workers', $orgId, $workerId);

if (!$worker) {
    session_flash('error', 'Worker not found.');
    redirect('org-admin/workers/index.php');
}

// Recent attendance - last 14 days.
$recentAttendance = db_all(
    'SELECT * FROM attendance WHERE worker_id = :worker_id AND date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) ORDER BY date DESC',
    ['worker_id' => $workerId]
);

// Assigned tasks.
$assignedTasks = db_all(
    'SELECT t.*, f.name AS farm_name FROM tasks t LEFT JOIN farms f ON f.id = t.farm_id
     WHERE t.assigned_to = :worker_id AND t.organization_id = :org_id ORDER BY t.deadline ASC',
    ['worker_id' => $workerId, 'org_id' => $orgId]
);

// Attendance rate this month = present days / working days so far (Mon-Sat counted as working days).
$monthStart = date('Y-m-01');
$today = date('Y-m-d');
$workingDaysSoFar = 0;
$cursor = new DateTime($monthStart);
$end = new DateTime($today);
while ($cursor <= $end) {
    if ((int) $cursor->format('N') !== 7) { // skip Sundays
        $workingDaysSoFar++;
    }
    $cursor->modify('+1 day');
}

$presentCount = (float) db_value("SELECT COUNT(*) FROM attendance WHERE worker_id = :worker_id AND date >= :start AND date <= :end AND status = 'present'", ['worker_id' => $workerId, 'start' => $monthStart, 'end' => $today]);
$lateCount = (float) db_value("SELECT COUNT(*) FROM attendance WHERE worker_id = :worker_id AND date >= :start AND date <= :end AND status = 'late'", ['worker_id' => $workerId, 'start' => $monthStart, 'end' => $today]);
$halfCount = (float) db_value("SELECT COUNT(*) FROM attendance WHERE worker_id = :worker_id AND date >= :start AND date <= :end AND status = 'half_day'", ['worker_id' => $workerId, 'start' => $monthStart, 'end' => $today]);
$presentEquivalent = $presentCount + $lateCount + ($halfCount * 0.5);
$attendanceRate = $workingDaysSoFar > 0 ? ($presentEquivalent / $workingDaysSoFar) * 100 : 0;

render_header(['title' => 'Worker Profile - ' . $worker['name'], 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/workers/index.php') ?>">Workers</a></li>
        <li class="breadcrumb-item active"><?= e($worker['name']) ?></li>
      </ol></nav>

      <div class="content-card mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <h4 class="mb-1"><?= e($worker['name']) ?> <span class="text-muted small">(<?= e($worker['employee_id'] ?: '-') ?>)</span></h4>
            <p class="text-muted mb-0"><?= e($worker['role'] ?: 'No role set') ?> &middot; <?= e($worker['department'] ?: 'No department') ?></p>
          </div>
          <?php render_status_badge($worker['status']); ?>
        </div>
        <hr>
        <div class="row g-3">
          <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-semibold"><?= e($worker['phone'] ?: '-') ?></div></div>
          <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-semibold"><?= e($worker['email'] ?: '-') ?></div></div>
          <div class="col-md-3"><div class="text-muted small">Hire Date</div><div class="fw-semibold"><?= format_date($worker['hire_date']) ?></div></div>
          <div class="col-md-3"><div class="text-muted small">Hourly Rate</div><div class="fw-semibold"><?= $worker['hourly_rate'] !== null ? format_money((float) $worker['hourly_rate']) : '-' ?></div></div>
          <div class="col-md-3"><div class="text-muted small">Emergency Contact</div><div class="fw-semibold"><?= e($worker['emergency_contact'] ?: '-') ?></div></div>
          <div class="col-md-3"><div class="text-muted small">Portal Login</div><div class="fw-semibold"><?= $worker['user_id'] ? '<i class="bi bi-check-circle text-success"></i> Linked' : '<i class="bi bi-dash-circle text-muted"></i> None' ?></div></div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <?php render_stat_card('Attendance Rate (This Month)', number_format($attendanceRate, 1) . '%', null, 'bi-calendar-check', 'primary'); ?>
        </div>
        <div class="col-md-4">
          <?php render_stat_card('Open Tasks', (string) count(array_filter($assignedTasks, fn ($t) => !in_array($t['status'], ['completed', 'verified', 'canceled'], true))), null, 'bi-list-task', 'info'); ?>
        </div>
        <div class="col-md-4">
          <?php render_stat_card('Attendance Records (14 days)', (string) count($recentAttendance), null, 'bi-clock-history', 'secondary'); ?>
        </div>
      </div>

      <ul class="nav nav-tabs mb-3" id="workerTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button">Recent Attendance</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button">Assigned Tasks</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-attendance">
          <div class="content-card">
            <?php if (!$recentAttendance): ?>
              <?php render_empty_state('No attendance records in the last 14 days.', 'bi-calendar-x'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php foreach ($recentAttendance as $a): ?>
                      <tr>
                        <td><?= format_date($a['date']) ?></td>
                        <td><?= e($a['clock_in'] ?: '-') ?></td>
                        <td><?= e($a['clock_out'] ?: '-') ?></td>
                        <td><?php render_status_badge($a['status']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="tab-pane fade" id="tab-tasks">
          <div class="content-card">
            <?php if (!$assignedTasks): ?>
              <?php render_empty_state('No tasks assigned to this worker yet.', 'bi-list-task'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Title</th><th>Farm</th><th>Priority</th><th>Deadline</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php foreach ($assignedTasks as $t): ?>
                      <tr>
                        <td><?= e($t['title']) ?></td>
                        <td><?= e($t['farm_name'] ?: '-') ?></td>
                        <td><span class="badge <?= status_badge_class($t['priority']) ?>"><?= e(humanize($t['priority'])) ?></span></td>
                        <td><?= format_date($t['deadline']) ?></td>
                        <td><?php render_status_badge($t['status']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
