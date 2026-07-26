<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$workers = tenant_all('workers', $orgId, 'ORDER BY name ASC');

$errors = [];

// Admin override: manually add/edit an attendance record (e.g. for paper timesheets).
if (is_post() && csrf_verify()) {
    $overrideWorkerId = clean_int($_POST['worker_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $clockIn = clean_string($_POST['clock_in'] ?? '');
    $clockOut = clean_string($_POST['clock_out'] ?? '');
    $status = $_POST['status'] ?? 'present';

    $worker = $overrideWorkerId ? tenant_find('workers', $orgId, $overrideWorkerId) : null;

    if (!$worker) {
        $errors['worker_id'] = 'Please select a valid worker.';
    }
    if (!$date || !strtotime($date)) {
        $errors['date'] = 'Please select a valid date.';
    }
    if (!in_array($status, ['present', 'absent', 'late', 'half_day'], true)) {
        $errors['status'] = 'Invalid status.';
    }

    if (!$errors) {
        $existing = db_one('SELECT id FROM attendance WHERE worker_id = :worker_id AND date = :date', ['worker_id' => $overrideWorkerId, 'date' => $date]);
        $data = [
            'clock_in' => $clockIn ?: null,
            'clock_out' => $clockOut ?: null,
            'status' => $status,
            'notes' => 'Manually recorded by ' . $currentUser['name'],
        ];
        if ($existing) {
            db_update('attendance', $data, 'id = :id', ['id' => $existing['id']]);
            session_flash('success', 'Attendance record updated.');
        } else {
            $data['worker_id'] = $overrideWorkerId;
            $data['date'] = $date;
            db_insert('attendance', $data);
            session_flash('success', 'Attendance record added.');
        }
        redirect('org-admin/workers/attendance.php?worker_id=' . $overrideWorkerId . '&month=' . date('n', strtotime($date)) . '&year=' . date('Y', strtotime($date)));
    }
    $GLOBALS['_page_errors'] = $errors;
}

$selectedWorkerId = (int) clean_int($_GET['worker_id'] ?? 0) ?: (int) ($workers[0]['id'] ?? 0);
$month = (int) clean_int($_GET['month'] ?? 0) ?: (int) date('n');
$year = (int) clean_int($_GET['year'] ?? 0) ?: (int) date('Y');
$month = max(1, min(12, $month));

$selectedWorker = $selectedWorkerId ? tenant_find('workers', $orgId, $selectedWorkerId) : null;

$records = [];
$summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0];
if ($selectedWorker) {
    $records = db_all(
        'SELECT * FROM attendance WHERE worker_id = :worker_id AND YEAR(date) = :year AND MONTH(date) = :month ORDER BY date ASC',
        ['worker_id' => $selectedWorkerId, 'year' => $year, 'month' => $month]
    );
    foreach ($records as $r) {
        if (isset($summary[$r['status']])) {
            $summary[$r['status']]++;
        }
    }
}

$monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];

render_header(['title' => 'Attendance', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Attendance</h4>
          <p class="text-muted small mb-0">Review clock-in/out records logged from the worker portal</p>
        </div>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Worker</label>
            <select name="worker_id" class="form-select">
              <?php foreach ($workers as $w): ?>
                <option value="<?= (int) $w['id'] ?>" <?= $selectedWorkerId === (int) $w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Month</label>
            <select name="month" class="form-select">
              <?php foreach ($monthNames as $num => $label): ?>
                <option value="<?= $num ?>" <?= $month === $num ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Year</label>
            <select name="year" class="form-select">
              <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal"><i class="bi bi-plus-lg"></i> Add Record</button>
          </div>
        </form>
      </div>

      <?php if (!$workers): ?>
        <div class="content-card"><?php render_empty_state('No workers yet. Add a worker first.', 'bi-person-workspace'); ?></div>
      <?php else: ?>
        <div class="row g-3 mb-3">
          <div class="col-sm-6 col-xl-3"><?php render_stat_card('Present', (string) $summary['present'], null, 'bi-check-circle', 'primary'); ?></div>
          <div class="col-sm-6 col-xl-3"><?php render_stat_card('Absent', (string) $summary['absent'], null, 'bi-x-circle', 'danger'); ?></div>
          <div class="col-sm-6 col-xl-3"><?php render_stat_card('Late', (string) $summary['late'], null, 'bi-clock', 'secondary'); ?></div>
          <div class="col-sm-6 col-xl-3"><?php render_stat_card('Half Day', (string) $summary['half_day'], null, 'bi-clock-history', 'info'); ?></div>
        </div>

        <div class="content-card">
          <h6 class="mb-3"><?= e($selectedWorker['name'] ?? '') ?> &middot; <?= $monthNames[$month] ?> <?= $year ?></h6>
          <?php if (!$records): ?>
            <?php render_empty_state('No attendance records for this worker in this period.', 'bi-calendar-x'); ?>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-app">
                <thead><tr><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($records as $r): ?>
                    <tr>
                      <td><?= format_date($r['date']) ?></td>
                      <td><?= e($r['clock_in'] ?: '-') ?></td>
                      <td><?= e($r['clock_out'] ?: '-') ?></td>
                      <td><?php render_status_badge($r['status']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<?php modal_open('addAttendanceModal', 'Add / Edit Attendance Record'); ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="mb-2">
    <label class="form-label small">Worker</label>
    <select name="worker_id" class="form-select" required>
      <?php foreach ($workers as $w): ?>
        <option value="<?= (int) $w['id'] ?>" <?= $selectedWorkerId === (int) $w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-2">
    <label class="form-label small">Date</label>
    <input type="date" name="date" class="form-control" required>
  </div>
  <div class="row">
    <div class="col-6 mb-2">
      <label class="form-label small">Clock In</label>
      <input type="time" name="clock_in" class="form-control">
    </div>
    <div class="col-6 mb-2">
      <label class="form-label small">Clock Out</label>
      <input type="time" name="clock_out" class="form-control">
    </div>
  </div>
  <div class="mb-2">
    <label class="form-label small">Status</label>
    <select name="status" class="form-select">
      <option value="present">Present</option>
      <option value="absent">Absent</option>
      <option value="late">Late</option>
      <option value="half_day">Half Day</option>
    </select>
  </div>
  <p class="text-muted small">Use this only for manual corrections (e.g. paper timesheets). Records logged via the worker portal's own clock-in/out do not need to be re-entered here. If a record already exists for this worker + date, it will be overwritten.</p>
  <button type="submit" class="btn btn-primary w-100">Save Record</button>
</form>
<?php modal_close(); ?>

<?php render_footer(['context' => 'org-admin']); ?>
