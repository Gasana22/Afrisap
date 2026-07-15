<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$totalFarms = (int) db_value('SELECT COUNT(*) FROM farms');
$totalCrops = (int) db_value('SELECT COUNT(*) FROM crop_cycles');
$totalAnimals = (int) db_value('SELECT COUNT(*) FROM animals');
$totalWorkers = (int) db_value('SELECT COUNT(*) FROM workers');

$planDistribution = platform_group_totals('organizations', 'subscription_plan', 'COUNT(*)');

render_header(['title' => 'Usage Reports', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Platform Usage</h4>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Farms', number_format($totalFarms), null, 'bi-geo-alt', 'primary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Crop Cycles', number_format($totalCrops), null, 'bi-flower1', 'secondary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Animals', number_format($totalAnimals), null, 'bi-egg-fried', 'info'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Workers', number_format($totalWorkers), null, 'bi-person-workspace', 'danger'); ?>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Organizations by Plan</h6></div>
            <?php if ($planDistribution['labels']): ?>
              <canvas id="planChart" height="100"></canvas>
            <?php else: ?>
              <?php render_empty_state('No organizations yet.'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Storage Usage</h6></div>
            <p class="text-muted small">Per-tenant storage tracking is not yet metered by the platform. Showing an estimated placeholder based on uploaded file counts.</p>
            <?php
            $uploadCount = (int) db_value("SELECT COUNT(*) FROM trace_qr_codes") + (int) db_value("SELECT COUNT(*) FROM crop_operations WHERE photo_urls IS NOT NULL");
            ?>
            <?php render_stat_card('Estimated Files Stored', number_format($uploadCount), null, 'bi-hdd', 'primary'); ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php if ($planDistribution['labels']): ?>
  renderDoughnutChart('planChart', <?= json_encode($planDistribution['labels']) ?>, <?= json_encode($planDistribution['data']) ?>);
  <?php endif; ?>
});
</script>
<?php render_footer(['context' => 'admin', 'js' => ['charts']]); ?>
