<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

// --- Key metrics ---
$totalLandArea = (float) db_value('SELECT COALESCE(SUM(size), 0) FROM farms WHERE organization_id = :id', ['id' => $orgId]);
$revenueThisMonth = (float) db_value(
    "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE organization_id = :id AND type = 'income' AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
    ['id' => $orgId]
);
$revenueLastMonth = (float) db_value(
    "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE organization_id = :id AND type = 'income'
     AND transaction_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
     AND transaction_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')",
    ['id' => $orgId]
);
$activeCrops = tenant_count('crop_cycles', $orgId, "AND status != 'completed'");
$totalWorkers = tenant_count('workers', $orgId, "AND status = 'active'");

function pct_change(float $current, float $previous): ?string
{
    if ($previous == 0.0) {
        return $current > 0 ? '+100%' : null;
    }
    $change = (($current - $previous) / $previous) * 100;

    return sprintf('%s%.1f%%', $change >= 0 ? '+' : '', $change);
}

// --- Monthly yield analysis (per crop type, last 6 months) ---
$cropTypes = db_all(
    'SELECT DISTINCT crop_type FROM crop_cycles WHERE organization_id = :id ORDER BY crop_type LIMIT 6',
    ['id' => $orgId]
);
$yieldSeries = [];
foreach ($cropTypes as $row) {
    $type = $row['crop_type'];
    $yieldSeries[$type] = chart_monthly_series(
        $orgId,
        'harvest_records hr JOIN crop_cycles cc ON cc.id = hr.crop_cycle_id',
        'hr.harvest_date',
        'hr.quantity',
        'AND cc.crop_type = :crop_type',
        ['crop_type' => $type]
    );
}
$defaultCrop = array_key_first($yieldSeries);

// --- Production overview ---
$totalProduction = (float) db_value(
    'SELECT COALESCE(SUM(hr.quantity), 0) FROM harvest_records hr JOIN crop_cycles cc ON cc.id = hr.crop_cycle_id WHERE cc.organization_id = :id',
    ['id' => $orgId]
);
$latestCrop = db_one(
    "SELECT * FROM crop_cycles WHERE organization_id = :id ORDER BY start_date DESC LIMIT 1",
    ['id' => $orgId]
);

// --- Task management widget ---
$tasks = db_all(
    'SELECT t.*, w.name AS worker_name FROM tasks t
     JOIN workers w ON w.id = t.assigned_to
     WHERE t.organization_id = :id AND t.status NOT IN ("completed", "verified", "canceled")
     ORDER BY t.deadline ASC LIMIT 5',
    ['id' => $orgId]
);

// --- Vegetable/crop harvest summary (recent) ---
$harvestSummary = db_all(
    'SELECT cc.crop_type, SUM(hr.quantity) AS total_qty
     FROM harvest_records hr JOIN crop_cycles cc ON cc.id = hr.crop_cycle_id
     WHERE cc.organization_id = :id AND hr.harvest_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
     GROUP BY cc.crop_type ORDER BY total_qty DESC LIMIT 5',
    ['id' => $orgId]
);

// --- Recent activities ---
$recentActivities = db_all(
    'SELECT co.activity_type, co.activity_date, co.description, cc.crop_type, co.created_at
     FROM crop_operations co JOIN crop_cycles cc ON cc.id = co.crop_cycle_id
     WHERE cc.organization_id = :id ORDER BY co.created_at DESC LIMIT 5',
    ['id' => $orgId]
);

// --- Alerts ---
$lowStockItems = db_all(
    'SELECT name, quantity, unit, reorder_level FROM inventory_items
     WHERE organization_id = :id AND status = "active" AND quantity <= reorder_level ORDER BY quantity ASC LIMIT 5',
    ['id' => $orgId]
);
$upcomingDeadlines = db_all(
    'SELECT title, deadline FROM tasks WHERE organization_id = :id AND status NOT IN ("completed", "verified", "canceled")
     AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY deadline ASC LIMIT 5',
    ['id' => $orgId]
);

render_header(['title' => 'Dashboard', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-lg-8">
          <div class="content-card h-100 d-flex flex-column justify-content-center">
            <h4 class="mb-1">Good <?= (int) date('G') < 12 ? 'Morning' : ((int) date('G') < 18 ? 'Afternoon' : 'Evening') ?>, <?= e(explode(' ', $currentUser['name'])[0]) ?>!</h4>
            <p class="text-muted mb-0">Optimize your farm operations with real-time insights.</p>
            <p class="text-muted small mb-0"><?= date('l, j M Y') ?></p>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="weather-widget h-100 d-flex justify-content-between align-items-center">
            <div>
              <div class="fs-3 fw-bold">24&deg;C</div>
              <div class="small">Feels like 26&deg;C</div>
              <div class="small">H: 27&deg; L: 10&deg;</div>
            </div>
            <div class="text-end">
              <i class="bi bi-cloud-fill display-5"></i>
              <div class="small mt-1"><?= e($organization['name']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Land Area', number_format($totalLandArea, 1) . ' acres', null, 'bi-geo-alt', 'primary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Revenue (This Month)', format_money($revenueThisMonth), pct_change($revenueThisMonth, $revenueLastMonth), 'bi-cash-coin', 'secondary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Active Crops', (string) $activeCrops, null, 'bi-flower1', 'info'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Workers', (string) $totalWorkers, null, 'bi-person-workspace', 'danger'); ?>
        </div>
      </div>

      <div class="content-card mb-4">
        <div class="content-card-header">
          <h5>Monthly Yield Analysis</h5>
          <ul class="nav crop-tabs">
            <?php foreach (array_keys($yieldSeries) as $i => $crop): ?>
              <li class="nav-item"><a class="nav-link <?= $i === 0 ? 'active' : '' ?>" href="#" data-crop="<?= e($crop) ?>"><?= e(humanize($crop)) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php if ($yieldSeries): ?>
          <canvas id="yieldChart" height="80"></canvas>
        <?php else: ?>
          <?php render_empty_state('No harvest records yet - yield trends will appear here once you log a harvest.'); ?>
        <?php endif; ?>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-5">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Production Overview</h6></div>
            <ul class="list-unstyled mb-0">
              <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Total Production</span><strong><?= number_format($totalProduction, 0) ?> units</strong></li>
              <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Crop Health</span><strong class="text-success">Good</strong></li>
              <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Latest Planting</span><strong><?= format_date($latestCrop['start_date'] ?? null) ?></strong></li>
              <li class="d-flex justify-content-between py-2"><span class="text-muted">Current Stage</span><strong><?= e(humanize($latestCrop['status'] ?? '-')) ?></strong></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card h-100">
            <div class="content-card-header">
              <h6>Task Management</h6>
              <a href="<?= base_url('org-admin/workers/tasks.php') ?>" class="btn btn-sm btn-primary">+ Add New Task</a>
            </div>
            <?php if ($tasks): ?>
              <?php foreach ($tasks as $task): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                  <div>
                    <div class="fw-semibold"><?= e($task['title']) ?></div>
                    <div class="small text-muted">Assigned to: <?= e($task['worker_name']) ?> &middot; Due: <?= format_date($task['deadline']) ?></div>
                  </div>
                  <?php render_status_badge($task['status']); ?>
                </div>
              <?php endforeach; ?>
              <a href="<?= base_url('org-admin/workers/tasks.php') ?>" class="small">View All &rarr;</a>
            <?php else: ?>
              <?php render_empty_state('No open tasks. Great job staying on top of things!'); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Harvest Summary (90 days)</h6></div>
            <?php if ($harvestSummary): ?>
              <?php foreach ($harvestSummary as $h): ?>
                <div class="d-flex justify-content-between py-2 border-bottom">
                  <span><?= e(humanize($h['crop_type'])) ?></span>
                  <strong><?= number_format($h['total_qty'], 0) ?> units</strong>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <?php render_empty_state('No harvests recorded in the last 90 days.'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Recent Activities</h6></div>
            <?php if ($recentActivities): ?>
              <?php foreach ($recentActivities as $a): ?>
                <div class="py-2 border-bottom">
                  <div class="fw-semibold"><?= e(humanize($a['activity_type'])) ?> &middot; <?= e(humanize($a['crop_type'])) ?></div>
                  <div class="small text-muted"><?= format_date($a['activity_date']) ?> - <?= e($a['description'] ?: 'No notes') ?></div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <?php render_empty_state('No field activity logged yet.'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Alerts & Notifications</h6></div>
            <?php if (!$lowStockItems && !$upcomingDeadlines): ?>
              <?php render_empty_state('No alerts right now.'); ?>
            <?php endif; ?>
            <?php foreach ($lowStockItems as $item): ?>
              <div class="py-2 border-bottom small"><i class="bi bi-exclamation-triangle text-warning me-1"></i> Low stock: <?= e($item['name']) ?> (<?= number_format($item['quantity'], 1) ?> <?= e($item['unit']) ?> left)</div>
            <?php endforeach; ?>
            <?php foreach ($upcomingDeadlines as $t): ?>
              <div class="py-2 border-bottom small"><i class="bi bi-clock text-info me-1"></i> Upcoming: <?= e($t['title']) ?> due <?= format_date($t['deadline']) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
window.yieldChartData = <?= json_encode(array_map(fn ($s) => $s['data'], $yieldSeries)) ?>;
<?php if ($yieldSeries): ?>
document.addEventListener('DOMContentLoaded', () => {
    window.yieldChartInstance = renderBarChart(
        'yieldChart',
        <?= json_encode($yieldSeries[$defaultCrop]['labels']) ?>,
        <?= json_encode($yieldSeries[$defaultCrop]['data']) ?>,
        '<?= e(humanize($defaultCrop)) ?> Yield'
    );
});
<?php endif; ?>
</script>

<?php render_footer(['context' => 'org-admin', 'js' => ['charts']]); ?>
