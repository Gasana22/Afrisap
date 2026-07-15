<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$statusFilter = clean_string($_GET['status'] ?? '');

$where = 'WHERE 1 = 1';
$params = [];
if ($statusFilter !== '') {
    $where .= ' AND s.status = :status';
    $params['status'] = $statusFilter;
}

$pagination = paginate_params(25);
$totalRows = (int) db_value("SELECT COUNT(*) FROM subscriptions s $where", $params);
$limit = (int) $pagination['per_page'];
$offset = (int) $pagination['offset'];

$payments = db_all(
    "SELECT s.*, o.name AS org_name, sp.name AS plan_name
     FROM subscriptions s
     JOIN organizations o ON o.id = s.organization_id
     LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
     $where
     ORDER BY s.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

render_header(['title' => 'Payments', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Payments</h4>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
              <option value="">All statuses</option>
              <?php foreach (['active', 'canceled', 'expired', 'pending'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(humanize($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$payments): ?>
          <?php render_empty_state('No payment records found.', 'bi-credit-card'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr><th>Organization</th><th>Plan</th><th>Method</th><th>Reference</th><th>Amount</th><th>Status</th><th>Date</th></tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $p): ?>
                  <tr>
                    <td><?= e($p['org_name']) ?></td>
                    <td><?= e($p['plan_name'] ?? '-') ?></td>
                    <td><?= e($p['payment_method'] ? humanize($p['payment_method']) : '-') ?></td>
                    <td class="small text-muted"><?= e($p['payment_reference'] ?: '-') ?></td>
                    <td><?= format_money((float) $p['amount']) ?></td>
                    <td><?php render_status_badge($p['status']); ?></td>
                    <td><?= format_date($p['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php render_pagination($pagination['page'], $pagination['per_page'], $totalRows, base_url('admin/subscriptions/payments.php'), array_filter(['status' => $statusFilter])); ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
