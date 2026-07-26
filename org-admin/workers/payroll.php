<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$workers = tenant_all('workers', $orgId, 'ORDER BY name ASC');
$errors = [];

if (is_post() && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        $workerId = clean_int($_POST['worker_id'] ?? 0);
        $periodStart = $_POST['pay_period_start'] ?? '';
        $periodEnd = $_POST['pay_period_end'] ?? '';
        $deductions = clean_float($_POST['deductions'] ?? 0) ?? 0.0;

        $worker = $workerId ? tenant_find('workers', $orgId, $workerId) : null;

        if (!$worker) {
            $errors['worker_id'] = 'Please select a valid worker.';
        }
        if (!$periodStart || !strtotime($periodStart)) {
            $errors['pay_period_start'] = 'Please select a valid start date.';
        }
        if (!$periodEnd || !strtotime($periodEnd)) {
            $errors['pay_period_end'] = 'Please select a valid end date.';
        }
        if (!$errors && strtotime($periodEnd) < strtotime($periodStart)) {
            $errors['pay_period_end'] = 'End date must be on or after the start date.';
        }

        if (!$errors) {
            // Sum worked hours from attendance in the range. TIMEDIFF(clock_out, clock_in)
            // returns a TIME; SUM() on TIME values in MySQL/MariaDB coerces to seconds-as-number,
            // so we convert manually via TIME_TO_SEC for reliability. Rows with a NULL clock_out
            // (worker never clocked out, e.g. forgot or still on shift) are excluded from the sum.
            $totalSeconds = (float) db_value(
                "SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(clock_out, clock_in))), 0)
                 FROM attendance
                 WHERE worker_id = :worker_id AND date >= :start AND date <= :end
                 AND clock_in IS NOT NULL AND clock_out IS NOT NULL",
                ['worker_id' => $workerId, 'start' => $periodStart, 'end' => $periodEnd]
            );
            $hoursWorked = round($totalSeconds / 3600, 2);
            $hourlyRate = (float) ($worker['hourly_rate'] ?? 0);
            $grossAmount = round($hoursWorked * $hourlyRate, 2);
            $netAmount = round($grossAmount - $deductions, 2);

            $payrollId = tenant_insert('payroll', $orgId, [
                'worker_id' => $workerId,
                'pay_period_start' => $periodStart,
                'pay_period_end' => $periodEnd,
                'hours_worked' => $hoursWorked,
                'gross_amount' => $grossAmount,
                'deductions' => $deductions,
                'net_amount' => $netAmount,
                'status' => 'draft',
            ]);
            session_flash('success', 'Payroll entry generated (' . $hoursWorked . ' hours).');
            redirect('org-admin/workers/payroll.php');
        }
    } elseif ($action === 'approve' || $action === 'pay') {
        $payrollId = clean_int($_POST['payroll_id'] ?? 0);
        $record = tenant_find('payroll', $orgId, $payrollId);
        if ($record) {
            if ($action === 'approve' && $record['status'] === 'draft') {
                tenant_update('payroll', $orgId, $payrollId, ['status' => 'approved']);
                session_flash('success', 'Payroll entry approved.');
            } elseif ($action === 'pay' && $record['status'] === 'approved') {
                tenant_update('payroll', $orgId, $payrollId, ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
                session_flash('success', 'Payroll entry marked as paid.');
            } else {
                session_flash('error', 'Invalid status transition.');
            }
        } else {
            session_flash('error', 'Payroll record not found.');
        }
        redirect('org-admin/workers/payroll.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

$filterWorkerId = (int) clean_int($_GET['worker_id'] ?? 0);
$filterStart = $_GET['start'] ?? '';
$filterEnd = $_GET['end'] ?? '';

$extraSql = '';
$params = [];
if ($filterWorkerId) {
    $extraSql .= ' AND worker_id = :worker_id';
    $params['worker_id'] = $filterWorkerId;
}
if ($filterStart && strtotime($filterStart)) {
    $extraSql .= ' AND pay_period_start >= :start';
    $params['start'] = $filterStart;
}
if ($filterEnd && strtotime($filterEnd)) {
    $extraSql .= ' AND pay_period_end <= :end';
    $params['end'] = $filterEnd;
}
$extraSql .= ' ORDER BY pay_period_start DESC';

$payrollRecords = db_all(
    "SELECT p.*, w.name AS worker_name FROM payroll p JOIN workers w ON w.id = p.worker_id
     WHERE p.organization_id = :org_id $extraSql",
    array_merge(['org_id' => $orgId], $params)
);

render_header(['title' => 'Payroll', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Payroll</h4>
          <p class="text-muted small mb-0">Generate and track worker pay periods</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generatePayrollModal"><i class="bi bi-plus-lg"></i> Generate Payroll</button>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Worker</label>
            <select name="worker_id" class="form-select">
              <option value="">All Workers</option>
              <?php foreach ($workers as $w): ?>
                <option value="<?= (int) $w['id'] ?>" <?= $filterWorkerId === (int) $w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Period Start From</label>
            <input type="date" name="start" class="form-control" value="<?= e($filterStart) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Period End Until</label>
            <input type="date" name="end" class="form-control" value="<?= e($filterEnd) ?>">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
            <a href="<?= base_url('org-admin/workers/payroll.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$payrollRecords): ?>
          <?php render_empty_state('No payroll records yet. Generate one to get started.', 'bi-cash-stack'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Worker</th><th>Period</th><th>Hours</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payrollRecords as $p): ?>
                  <tr>
                    <td><?= e($p['worker_name']) ?></td>
                    <td><?= format_date($p['pay_period_start']) ?> - <?= format_date($p['pay_period_end']) ?></td>
                    <td><?= number_format((float) $p['hours_worked'], 2) ?></td>
                    <td><?= format_money((float) $p['gross_amount']) ?></td>
                    <td><?= format_money((float) $p['deductions']) ?></td>
                    <td><strong><?= format_money((float) $p['net_amount']) ?></strong></td>
                    <td><?php render_status_badge($p['status']); ?></td>
                    <td class="text-end">
                      <?php if ($p['status'] === 'draft'): ?>
                        <form method="post" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="approve">
                          <input type="hidden" name="payroll_id" value="<?= (int) $p['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-primary">Approve</button>
                        </form>
                      <?php elseif ($p['status'] === 'approved'): ?>
                        <form method="post" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="pay">
                          <input type="hidden" name="payroll_id" value="<?= (int) $p['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-success">Mark Paid</button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted small">Paid <?= format_date($p['paid_at'] ?? null) ?></span>
                      <?php endif; ?>
                    </td>
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

<?php modal_open('generatePayrollModal', 'Generate Payroll Entry'); ?>
<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="generate">
  <div class="mb-2">
    <label class="form-label small">Worker</label>
    <select name="worker_id" class="form-select" required>
      <option value="">-- Select worker --</option>
      <?php foreach ($workers as $w): ?>
        <option value="<?= (int) $w['id'] ?>"><?= e($w['name']) ?> <?= $w['hourly_rate'] !== null ? '(' . format_money((float) $w['hourly_rate']) . '/hr)' : '' ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="row">
    <div class="col-6 mb-2">
      <label class="form-label small">Period Start</label>
      <input type="date" name="pay_period_start" class="form-control" required>
    </div>
    <div class="col-6 mb-2">
      <label class="form-label small">Period End</label>
      <input type="date" name="pay_period_end" class="form-control" required>
    </div>
  </div>
  <div class="mb-2">
    <label class="form-label small">Deductions</label>
    <input type="number" step="0.01" name="deductions" class="form-control" value="0">
  </div>
  <p class="text-muted small">Hours worked and gross amount are calculated automatically from attendance records and the worker's hourly rate.</p>
  <button type="submit" class="btn btn-primary w-100">Generate</button>
</form>
<?php modal_close(); ?>

<?php render_footer(['context' => 'org-admin']); ?>
