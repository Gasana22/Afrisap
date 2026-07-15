<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$rows = db_all(
    "SELECT w.id AS worker_id, w.name AS worker_name, w.department,
            COUNT(t.id) AS tasks_completed,
            AVG(CASE WHEN t.completed_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, t.created_at, t.completed_at) END) AS avg_hours
     FROM tasks t
     JOIN workers w ON w.id = t.assigned_to
     WHERE t.organization_id = :id AND t.status IN ('completed', 'verified')
       AND ((t.completed_at IS NOT NULL AND DATE(t.completed_at) BETWEEN :date_from AND :date_to)
            OR (t.completed_at IS NULL AND t.updated_at BETWEEN :date_from2 AND :date_to2))
     GROUP BY w.id, w.name, w.department
     ORDER BY tasks_completed DESC",
    ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'date_from2' => $dateFrom . ' 00:00:00', 'date_to2' => $dateTo . ' 23:59:59']
);

$totalTasksCompleted = array_sum(array_map(fn ($r) => (int) $r['tasks_completed'], $rows));

$exportParams = http_build_query(['report' => 'productivity', 'date_from' => $dateFrom, 'date_to' => $dateTo]);

render_header(['title' => 'Worker Productivity Report', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Worker Productivity Report</h4>
          <p class="text-muted small mb-0">Tasks completed per worker and average completion time.</p>
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
          <div class="col-md-3">
            <label class="form-label small">From</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">To</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/reporting/productivity.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6">
          <?php render_stat_card('Workers with Completed Tasks', (string) count($rows), null, 'bi-person-workspace', 'primary'); ?>
        </div>
        <div class="col-sm-6">
          <?php render_stat_card('Total Tasks Completed', (string) $totalTasksCompleted, null, 'bi-check2-square', 'secondary'); ?>
        </div>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Productivity by Worker</h6></div>
        <?php if (!$rows): ?>
          <?php render_empty_state('No completed tasks found in the selected period.', 'bi-check2-square'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead>
                <tr>
                  <th>Worker</th>
                  <th>Department</th>
                  <th class="text-end">Tasks Completed</th>
                  <th class="text-end">Avg. Completion Time</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?= e($r['worker_name']) ?></td>
                    <td><?= e($r['department'] ?: '-') ?></td>
                    <td class="text-end fw-semibold"><?= (int) $r['tasks_completed'] ?></td>
                    <td class="text-end"><?= $r['avg_hours'] !== null ? number_format((float) $r['avg_hours'], 1) . ' hrs' : '-' ?></td>
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
