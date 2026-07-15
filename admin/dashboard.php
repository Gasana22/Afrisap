<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/lib.php';

$admin = require_platform_admin();

// --- Key platform metrics ---
$totalOrganizations = (int) db_value('SELECT COUNT(*) FROM organizations');
$activeUsers = (int) db_value('SELECT COUNT(*) FROM users WHERE status = "active"');
$totalRevenue = (float) db_value("SELECT COALESCE(SUM(amount), 0) FROM subscriptions WHERE status = 'active'");
$totalFarms = (int) db_value('SELECT COUNT(*) FROM farms');

// --- Tenant growth: organizations created per month, across ALL tenants ---
$growthSeries = platform_monthly_series('organizations', 'created_at', 'COUNT(*)', '', [], 6);

// --- Recent activity feed ---
$recentOrganizations = db_all('SELECT id, name, slug, subscription_plan, created_at FROM organizations ORDER BY created_at DESC LIMIT 5');
$recentAuditLogs = db_all(
    'SELECT al.*, u.name AS user_name, o.name AS org_name
     FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     LEFT JOIN organizations o ON o.id = al.organization_id
     ORDER BY al.created_at DESC LIMIT 8'
);

render_header(['title' => 'Platform Dashboard', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="content-card mb-4">
        <h4 class="mb-1">Welcome back, <?= e(explode(' ', $admin['name'])[0]) ?>!</h4>
        <p class="text-muted mb-0">Platform-wide overview across all tenant organizations.</p>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Organizations', number_format($totalOrganizations), null, 'bi-building', 'primary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Active Users', number_format($activeUsers), null, 'bi-people', 'info'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Total Revenue', format_money($totalRevenue), null, 'bi-cash-coin', 'secondary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Platform Uptime', '99.9%', null, 'bi-hdd-network', 'danger'); ?>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="content-card h-100">
            <div class="content-card-header"><h5>Tenant Growth (Last 6 Months)</h5></div>
            <canvas id="growthChart" height="90"></canvas>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Quick Actions</h6></div>
            <div class="d-grid gap-2">
              <a href="<?= base_url('admin/tenants/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Register Tenant</a>
              <a href="<?= base_url('admin/users/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-people"></i> Manage Users</a>
              <a href="<?= base_url('admin/reports/usage.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-bar-chart"></i> Usage Reports</a>
            </div>
            <hr>
            <ul class="list-unstyled mb-0 small">
              <li class="d-flex justify-content-between py-1"><span class="text-muted">Total Farms</span><strong><?= number_format($totalFarms) ?></strong></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Newly Registered Organizations</h6></div>
            <?php if ($recentOrganizations): ?>
              <?php foreach ($recentOrganizations as $org): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                  <div>
                    <div class="fw-semibold"><?= e($org['name']) ?></div>
                    <div class="small text-muted"><?= e(humanize($org['subscription_plan'])) ?> plan &middot; <?= format_date($org['created_at']) ?></div>
                  </div>
                  <a href="<?= base_url('admin/tenants/view.php?id=' . $org['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <?php render_empty_state('No organizations registered yet.'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Recent Platform Activity</h6></div>
            <?php if ($recentAuditLogs): ?>
              <?php foreach ($recentAuditLogs as $log): ?>
                <div class="py-2 border-bottom small">
                  <strong><?= e($log['user_name'] ?? 'System') ?></strong> <?= e($log['action']) ?>d
                  <?= e(humanize($log['table_name'] ?? '')) ?>
                  <?php if ($log['org_name']): ?> in <em><?= e($log['org_name']) ?></em><?php endif; ?>
                  <div class="text-muted"><?= format_date($log['created_at'], 'd M Y H:i') ?></div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <?php render_empty_state('No activity recorded yet.'); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    renderBarChart('growthChart', <?= json_encode($growthSeries['labels']) ?>, <?= json_encode($growthSeries['data']) ?>, 'New Organizations');
});
</script>
<?php render_footer(['context' => 'admin', 'js' => ['charts']]); ?>
