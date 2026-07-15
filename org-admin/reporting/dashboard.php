<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

// --- Revenue this year vs last year ---
$revenueThisYear = (float) db_value(
    "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE organization_id = :id AND type = 'income' AND YEAR(transaction_date) = YEAR(CURDATE())",
    ['id' => $orgId]
);
$revenueLastYear = (float) db_value(
    "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE organization_id = :id AND type = 'income' AND YEAR(transaction_date) = YEAR(CURDATE()) - 1",
    ['id' => $orgId]
);
$revenueChange = null;
if ($revenueLastYear != 0.0) {
    $pct = (($revenueThisYear - $revenueLastYear) / $revenueLastYear) * 100;
    $revenueChange = sprintf('%s%.1f%%', $pct >= 0 ? '+' : '', $pct);
} elseif ($revenueThisYear > 0) {
    $revenueChange = '+100%';
}

// --- Crop cycles by status (doughnut) ---
$cropStatusRows = db_all(
    "SELECT status AS label, COUNT(*) AS total FROM crop_cycles WHERE organization_id = :id GROUP BY status ORDER BY total DESC",
    ['id' => $orgId]
);
$cropStatusChart = [
    'labels' => array_map(fn ($r) => humanize((string) $r['label']), $cropStatusRows),
    'data' => array_map(fn ($r) => (int) $r['total'], $cropStatusRows),
];
$totalCropCycles = array_sum($cropStatusChart['data']);

// --- Animals by species ---
$animalSpeciesRows = db_all(
    "SELECT species AS label, COUNT(*) AS total FROM animals WHERE organization_id = :id GROUP BY species ORDER BY total DESC",
    ['id' => $orgId]
);
$animalSpeciesChart = [
    'labels' => array_map(fn ($r) => humanize((string) $r['label']), $animalSpeciesRows),
    'data' => array_map(fn ($r) => (int) $r['total'], $animalSpeciesRows),
];
$totalAnimals = array_sum($animalSpeciesChart['data']);

// --- Worker headcount ---
$activeWorkers = tenant_count('workers', $orgId, "AND status = 'active'");

// --- Task completion rate this month ---
$totalTasksThisMonth = (int) db_value(
    "SELECT COUNT(*) FROM tasks WHERE organization_id = :id AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
    ['id' => $orgId]
);
$completedTasksThisMonth = (int) db_value(
    "SELECT COUNT(*) FROM tasks WHERE organization_id = :id AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND status IN ('completed', 'verified')",
    ['id' => $orgId]
);
$taskCompletionRate = $totalTasksThisMonth > 0 ? round(($completedTasksThisMonth / $totalTasksThisMonth) * 100, 1) : 0.0;

render_header(['title' => 'Reporting Dashboard', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Reporting Overview</h4>
          <p class="text-muted small mb-0">A high-level snapshot across your organization. Drill into a detailed report below.</p>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Revenue (This Year)', format_money($revenueThisYear), $revenueChange, 'bi-cash-coin', 'secondary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Crop Cycles', (string) $totalCropCycles, null, 'bi-flower1', 'primary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Animals', (string) $totalAnimals, null, 'bi-egg-fried', 'info'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Active Workers', (string) $activeWorkers, null, 'bi-person-workspace', 'danger'); ?>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-4">
          <?php render_stat_card('Task Completion Rate (This Month)', $taskCompletionRate . '%', $completedTasksThisMonth . ' of ' . $totalTasksThisMonth . ' tasks', 'bi-check2-square', 'secondary'); ?>
        </div>
        <div class="col-lg-4">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Crop Cycles by Status</h6></div>
            <?php if ($cropStatusChart['data']): ?>
              <canvas id="cropStatusChart" height="140"></canvas>
            <?php else: ?>
              <?php render_empty_state('No crop cycles recorded yet.', 'bi-flower1'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Animals by Species</h6></div>
            <?php if ($animalSpeciesChart['data']): ?>
              <canvas id="animalSpeciesChart" height="140"></canvas>
            <?php else: ?>
              <?php render_empty_state('No animals recorded yet.', 'bi-egg-fried'); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Detailed Reports</h6></div>
        <div class="row g-3">
          <div class="col-md-6 col-xl-4">
            <a href="<?= base_url('org-admin/reporting/operational.php') ?>" class="text-decoration-none">
              <div class="content-card h-100 border">
                <i class="bi bi-activity fs-3 text-primary"></i>
                <h6 class="mt-2 mb-1">Operational Report</h6>
                <p class="text-muted small mb-0">Daily activity: crop operations, attendance and tasks.</p>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-4">
            <a href="<?= base_url('org-admin/reporting/agricultural.php') ?>" class="text-decoration-none">
              <div class="content-card h-100 border">
                <i class="bi bi-flower1 fs-3 text-success"></i>
                <h6 class="mt-2 mb-1">Agricultural Report</h6>
                <p class="text-muted small mb-0">Yield per crop, expected vs actual.</p>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-4">
            <a href="<?= base_url('org-admin/reporting/livestock.php') ?>" class="text-decoration-none">
              <div class="content-card h-100 border">
                <i class="bi bi-egg-fried fs-3 text-warning"></i>
                <h6 class="mt-2 mb-1">Livestock Report</h6>
                <p class="text-muted small mb-0">Herd composition and production events.</p>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-4">
            <a href="<?= base_url('org-admin/reporting/financial.php') ?>" class="text-decoration-none">
              <div class="content-card h-100 border">
                <i class="bi bi-cash-coin fs-3 text-info"></i>
                <h6 class="mt-2 mb-1">Financial Report</h6>
                <p class="text-muted small mb-0">Income vs expense executive summary.</p>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-4">
            <a href="<?= base_url('org-admin/reporting/productivity.php') ?>" class="text-decoration-none">
              <div class="content-card h-100 border">
                <i class="bi bi-person-workspace fs-3 text-danger"></i>
                <h6 class="mt-2 mb-1">Worker Productivity</h6>
                <p class="text-muted small mb-0">Tasks completed per worker, average completion time.</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
<?php if ($cropStatusChart['data']): ?>
  renderDoughnutChart('cropStatusChart', <?= json_encode($cropStatusChart['labels']) ?>, <?= json_encode($cropStatusChart['data']) ?>);
<?php endif; ?>
<?php if ($animalSpeciesChart['data']): ?>
  renderDoughnutChart('animalSpeciesChart', <?= json_encode($animalSpeciesChart['labels']) ?>, <?= json_encode($animalSpeciesChart['data']) ?>);
<?php endif; ?>
});
</script>
<?php render_footer(['context' => 'org-admin', 'js' => ['charts']]); ?>
