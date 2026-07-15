<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$admin = require_platform_admin();

$id = clean_int($_GET['id'] ?? 0);
$organization = db_one('SELECT * FROM organizations WHERE id = :id', ['id' => $id]);

if (!$organization) {
    session_flash('error', 'Organization not found.');
    redirect('admin/tenants/index.php');
}

$farmCount = (int) db_value('SELECT COUNT(*) FROM farms WHERE organization_id = :id', ['id' => $id]);

$members = db_all(
    'SELECT ou.role, ou.status AS membership_status, ou.joined_at, u.name, u.email, u.status AS user_status
     FROM organization_users ou JOIN users u ON u.id = ou.user_id
     WHERE ou.organization_id = :id ORDER BY ou.joined_at ASC',
    ['id' => $id]
);

$subscriptionHistory = db_all(
    'SELECT s.*, sp.name AS plan_name
     FROM subscriptions s LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
     WHERE s.organization_id = :id ORDER BY s.created_at DESC',
    ['id' => $id]
);

$auditLogs = db_all(
    'SELECT al.*, u.name AS user_name FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     WHERE al.organization_id = :id ORDER BY al.created_at DESC LIMIT 20',
    ['id' => $id]
);

render_header(['title' => $organization['name'], 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h4 class="mb-1"><?= e($organization['name']) ?> <?php render_status_badge($organization['subscription_status']); ?></h4>
          <p class="text-muted small mb-0">Slug: <?= e($organization['slug']) ?> &middot; Plan: <?= e(humanize($organization['subscription_plan'])) ?> &middot; Registered <?= format_date($organization['created_at']) ?></p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('admin/tenants/edit.php?id=' . $id) ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
          <form method="post" action="<?= base_url('admin/tenants/suspend.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="redirect" value="view">
            <?php if ($organization['subscription_status'] === 'suspended'): ?>
              <button type="submit" class="btn btn-outline-success"><i class="bi bi-play-circle"></i> Reactivate</button>
            <?php else: ?>
              <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Suspend this tenant?');"><i class="bi bi-pause-circle"></i> Suspend</button>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Farms', (string) $farmCount, null, 'bi-geo-alt', 'primary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Users', (string) count($members), null, 'bi-people', 'info'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Email', $organization['email'] ?: '-', null, 'bi-envelope', 'secondary'); ?>
        </div>
        <div class="col-sm-6 col-xl-3">
          <?php render_stat_card('Phone', $organization['phone'] ?: '-', null, 'bi-telephone', 'danger'); ?>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Users</h6></div>
            <?php if ($members): ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                  <tbody>
                    <?php foreach ($members as $m): ?>
                      <tr>
                        <td><?= e($m['name']) ?><div class="small text-muted"><?= e($m['email']) ?></div></td>
                        <td><?= e(humanize($m['role'])) ?></td>
                        <td><?php render_status_badge($m['membership_status']); ?></td>
                        <td><?= format_date($m['joined_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <?php render_empty_state('No users in this organization yet.'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Subscription History</h6></div>
            <?php if ($subscriptionHistory): ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Plan</th><th>Amount</th><th>Status</th><th>Started</th></tr></thead>
                  <tbody>
                    <?php foreach ($subscriptionHistory as $s): ?>
                      <tr>
                        <td><?= e($s['plan_name'] ?? '-') ?></td>
                        <td><?= format_money((float) $s['amount']) ?></td>
                        <td><?php render_status_badge($s['status']); ?></td>
                        <td><?= format_date($s['starts_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <?php render_empty_state('No subscription records yet.'); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Recent Activity</h6></div>
        <?php if ($auditLogs): ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead><tr><th>When</th><th>User</th><th>Action</th><th>Table</th></tr></thead>
              <tbody>
                <?php foreach ($auditLogs as $log): ?>
                  <tr>
                    <td><?= format_date($log['created_at'], 'd M Y H:i') ?></td>
                    <td><?= e($log['user_name'] ?? 'System') ?></td>
                    <td><?= e(humanize($log['action'])) ?></td>
                    <td><?= e(humanize($log['table_name'] ?? '-')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <?php render_empty_state('No audit log entries for this organization yet.'); ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
