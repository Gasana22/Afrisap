<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$search = clean_string($_GET['search'] ?? '');
$statusFilter = clean_string($_GET['subscription_status'] ?? '');
$planFilter = clean_string($_GET['subscription_plan'] ?? '');

$where = 'WHERE 1 = 1';
$params = [];

if ($search !== '') {
    $where .= ' AND o.name LIKE :search';
    $params['search'] = '%' . $search . '%';
}
if ($statusFilter !== '') {
    $where .= ' AND o.subscription_status = :status';
    $params['status'] = $statusFilter;
}
if ($planFilter !== '') {
    $where .= ' AND o.subscription_plan = :plan';
    $params['plan'] = $planFilter;
}

$pagination = paginate_params(20);
$totalRows = (int) db_value("SELECT COUNT(*) FROM organizations o $where", $params);

$limit = (int) $pagination['per_page'];
$offset = (int) $pagination['offset'];

$organizations = db_all(
    "SELECT o.*,
        (SELECT COUNT(*) FROM farms f WHERE f.organization_id = o.id) AS farm_count,
        (SELECT COUNT(*) FROM organization_users ou WHERE ou.organization_id = o.id) AS user_count
     FROM organizations o
     $where
     ORDER BY o.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

render_header(['title' => 'Organizations', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Organizations</h4>
        <a href="<?= base_url('admin/tenants/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Register Tenant</a>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Search by name</label>
            <input type="text" name="search" class="form-control" placeholder="Organization name..." value="<?= e($search) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Subscription Status</label>
            <select name="subscription_status" class="form-select">
              <option value="">All statuses</option>
              <?php foreach (['active', 'suspended', 'expired', 'trial'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(humanize($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Plan</label>
            <select name="subscription_plan" class="form-select">
              <option value="">All plans</option>
              <?php foreach (['free', 'basic', 'professional', 'enterprise'] as $p): ?>
                <option value="<?= $p ?>" <?= $planFilter === $p ? 'selected' : '' ?>><?= e(humanize($p)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$organizations): ?>
          <?php render_empty_state('No organizations match your filters.', 'bi-building'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Plan</th>
                  <th>Status</th>
                  <th>Farms</th>
                  <th>Users</th>
                  <th>Created</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($organizations as $org): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= e($org['name']) ?></div>
                      <div class="small text-muted"><?= e($org['slug']) ?></div>
                    </td>
                    <td><?= e(humanize($org['subscription_plan'])) ?></td>
                    <td><?php render_status_badge($org['subscription_status']); ?></td>
                    <td><?= (int) $org['farm_count'] ?></td>
                    <td><?= (int) $org['user_count'] ?></td>
                    <td><?= format_date($org['created_at']) ?></td>
                    <td class="text-end">
                      <a href="<?= base_url('admin/tenants/view.php?id=' . $org['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                      <a href="<?= base_url('admin/tenants/edit.php?id=' . $org['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                      <form method="post" action="<?= base_url('admin/tenants/suspend.php') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $org['id'] ?>">
                        <input type="hidden" name="redirect" value="index">
                        <?php if ($org['subscription_status'] === 'suspended'): ?>
                          <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-play-circle"></i> Reactivate</button>
                        <?php else: ?>
                          <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Suspend this tenant?');"><i class="bi bi-pause-circle"></i> Suspend</button>
                        <?php endif; ?>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php
          render_pagination(
              $pagination['page'],
              $pagination['per_page'],
              $totalRows,
              base_url('admin/tenants/index.php'),
              array_filter(['search' => $search, 'subscription_status' => $statusFilter, 'subscription_plan' => $planFilter])
          );
          ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
