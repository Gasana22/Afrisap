<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 year'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$season = $_GET['season'] ?? '';

$seasonWhere = '';
$params = ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo];
if ($season !== '') {
    $seasonWhere = ' AND cc.season = :season';
    $params['season'] = $season;
}

// --- Total yield per crop type (from harvest records within range) ---
$yieldByCrop = db_all(
    "SELECT cc.crop_type, SUM(hr.quantity) AS total_yield, COUNT(DISTINCT cc.id) AS cycle_count
     FROM harvest_records hr
     JOIN crop_cycles cc ON cc.id = hr.crop_cycle_id
     WHERE cc.organization_id = :id AND hr.harvest_date BETWEEN :date_from AND :date_to $seasonWhere
     GROUP BY cc.crop_type ORDER BY total_yield DESC",
    $params
);

// --- Expected vs actual yield per crop cycle within range ---
$rangeParams = $params;
$rangeParams['date_from2'] = $dateFrom;
$rangeParams['date_to2'] = $dateTo;
$expectedVsActual = db_all(
    "SELECT cc.id, cc.crop_type, cc.variety, cc.season, cc.crop_batch_id, cc.status,
            cc.expected_yield, cc.actual_yield, cc.start_date, cc.end_date
     FROM crop_cycles cc
     WHERE cc.organization_id = :id AND (cc.start_date BETWEEN :date_from AND :date_to OR cc.end_date BETWEEN :date_from2 AND :date_to2) $seasonWhere
     ORDER BY cc.start_date DESC",
    $rangeParams
);

$seasons = db_all(
    "SELECT DISTINCT season FROM crop_cycles WHERE organization_id = :id AND season IS NOT NULL AND season != '' ORDER BY season",
    ['id' => $orgId]
);

$totalExpected = array_sum(array_map(fn ($r) => (float) $r['expected_yield'], $expectedVsActual));
$totalActual = array_sum(array_map(fn ($r) => (float) $r['actual_yield'], $expectedVsActual));

$chartLabels = array_map(fn ($r) => humanize($r['crop_type']), $yieldByCrop);
$chartData = array_map(fn ($r) => (float) $r['total_yield'], $yieldByCrop);

$exportParams = http_build_query(['report' => 'agricultural', 'date_from' => $dateFrom, 'date_to' => $dateTo, 'season' => $season]);

render_header(['title' => 'Agricultural Report', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Agricultural Report</h4>
          <p class="text-muted small mb-0">Yield and crop cycle performance for the selected period.</p>
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
          <div class="col-md-3">
            <label class="form-label small">Season</label>
            <select name="season" class="form-select">
              <option value="">All Seasons</option>
              <?php foreach ($seasons as $s): ?>
                <option value="<?= e($s['season']) ?>" <?= $season === $s['season'] ? 'selected' : '' ?>><?= e($s['season']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/reporting/agricultural.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <?php render_stat_card('Total Yield (harvested)', number_format(array_sum($chartData), 0) . ' units', null, 'bi-basket', 'primary'); ?>
        </div>
        <div class="col-sm-4">
          <?php render_stat_card('Expected Yield', number_format($totalExpected, 0) . ' units', null, 'bi-graph-up', 'info'); ?>
        </div>
        <div class="col-sm-4">
          <?php
          $yieldVariance = $totalExpected > 0 ? round((($totalActual - $totalExpected) / $totalExpected) * 100, 1) : null;
          render_stat_card('Actual Yield', number_format($totalActual, 0) . ' units', $yieldVariance !== null ? sprintf('%s%.1f%%', $yieldVariance >= 0 ? '+' : '', $yieldVariance) : null, 'bi-clipboard-check', 'secondary');
          ?>
        </div>
      </div>

      <div class="content-card mb-4">
        <div class="content-card-header"><h6>Yield by Crop Type</h6></div>
        <?php if ($chartData): ?>
          <canvas id="yieldByCropChart" height="90"></canvas>
        <?php else: ?>
          <?php render_empty_state('No harvest records in the selected period.', 'bi-basket'); ?>
        <?php endif; ?>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Expected vs Actual Yield by Crop Cycle</h6></div>
        <?php if (!$expectedVsActual): ?>
          <?php render_empty_state('No crop cycles found in the selected period.', 'bi-flower1'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead>
                <tr>
                  <th>Batch</th>
                  <th>Crop</th>
                  <th>Variety</th>
                  <th>Season</th>
                  <th>Status</th>
                  <th class="text-end">Expected Yield</th>
                  <th class="text-end">Actual Yield</th>
                  <th class="text-end">Variance</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($expectedVsActual as $cc): ?>
                  <?php
                  $expected = (float) $cc['expected_yield'];
                  $actual = (float) $cc['actual_yield'];
                  $variance = $expected > 0 ? (($actual - $expected) / $expected) * 100 : null;
                  ?>
                  <tr>
                    <td><?= e($cc['crop_batch_id']) ?></td>
                    <td><?= e(humanize($cc['crop_type'])) ?></td>
                    <td><?= e($cc['variety'] ?: '-') ?></td>
                    <td><?= e($cc['season'] ?: '-') ?></td>
                    <td><?php render_status_badge($cc['status']); ?></td>
                    <td class="text-end"><?= number_format($expected, 1) ?></td>
                    <td class="text-end"><?= number_format($actual, 1) ?></td>
                    <td class="text-end <?= $variance !== null && $variance < 0 ? 'text-danger' : 'text-success' ?>">
                      <?= $variance !== null ? sprintf('%s%.1f%%', $variance >= 0 ? '+' : '', $variance) : '-' ?>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
<?php if ($chartData): ?>
  renderBarChart('yieldByCropChart', <?= json_encode($chartLabels) ?>, <?= json_encode($chartData) ?>, 'Yield');
<?php endif; ?>
});
</script>
<?php render_footer(['context' => 'org-admin', 'js' => ['charts']]); ?>
