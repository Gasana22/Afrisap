<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$months = (int) ($_GET['months'] ?? 6);
$months = max(3, min(24, $months));

$newOrgsSeries = platform_monthly_series('organizations', 'created_at', 'COUNT(*)', '', [], $months);
$newSubsSeries = platform_monthly_series('subscriptions', 'created_at', 'COUNT(*)', '', [], $months);

$totalNewOrgs = (int) array_sum($newOrgsSeries['data']);
$totalNewSubs = (int) array_sum($newSubsSeries['data']);

render_header(['title' => 'Growth Report', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tenant Growth</h4>
        <form method="get" class="d-flex gap-2">
          <select name="months" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ([3, 6, 12, 24] as $m): ?>
              <option value="<?= $m ?>" <?= $months === $m ? 'selected' : '' ?>>Last <?= $m ?> months</option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('New Organizations', number_format($totalNewOrgs), null, 'bi-building-add', 'primary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('New Subscriptions', number_format($totalNewSubs), null, 'bi-credit-card', 'secondary'); ?>
        </div>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>New Organizations &amp; Subscriptions per Month</h6></div>
        <canvas id="growthChart" height="90"></canvas>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  renderMultiLineChart('growthChart', <?= json_encode($newOrgsSeries['labels']) ?>, [
    { label: 'New Organizations', data: <?= json_encode($newOrgsSeries['data']) ?> },
    { label: 'New Subscriptions', data: <?= json_encode($newSubsSeries['data']) ?> },
  ]);
});
</script>
<?php render_footer(['context' => 'admin', 'js' => ['charts']]); ?>
