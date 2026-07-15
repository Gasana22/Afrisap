<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$status = clean_string($_GET['status'] ?? '');
$search = clean_string($_GET['search'] ?? '');

$conditions = [];
$params = [];

if ($status !== '' && in_array($status, CROP_CYCLE_STATUSES, true)) {
    $conditions[] = 'cc.status = :status';
    $params['status'] = $status;
}

if ($search !== '') {
    $conditions[] = '(cc.crop_type LIKE :search_type OR cc.crop_batch_id LIKE :search_batch)';
    $params['search_type'] = "%$search%";
    $params['search_batch'] = "%$search%";
}

$extraSql = $conditions ? ('AND ' . implode(' AND ', $conditions)) : '';

$cycles = db_all(
    "SELECT cc.*, f.name AS farm_name, p.name AS plot_name
     FROM crop_cycles cc
     JOIN farms f ON f.id = cc.farm_id
     JOIN plots p ON p.id = cc.plot_id
     WHERE cc.organization_id = :organization_id $extraSql
     ORDER BY cc.created_at DESC",
    array_merge(['organization_id' => $orgId], $params)
);

render_header(['title' => 'Crop Management', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Crop Management</h4>
          <p class="text-muted small mb-0">Track crop cycles from planning through sale.</p>
        </div>
        <a href="<?= base_url('org-admin/crops/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Start New Crop Cycle</a>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Crop type or batch ID" value="<?= e($search) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
              <option value="">All Statuses</option>
              <?php foreach (CROP_CYCLE_STATUSES as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(humanize($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/crops/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$cycles): ?>
          <?php render_empty_state('No crop cycles yet. Start your first crop cycle to begin tracking.', 'bi-flower1'); ?>
        <?php else: ?>
          <table class="table-app">
            <thead>
              <tr>
                <th>Batch ID</th>
                <th>Crop</th>
                <th>Farm / Plot</th>
                <th>Season</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>Budget</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cycles as $c): ?>
                <tr>
                  <td><code><?= e($c['crop_batch_id']) ?></code></td>
                  <td><?= e($c['crop_type']) ?><?= $c['variety'] ? '<span class="text-muted small"> (' . e($c['variety']) . ')</span>' : '' ?></td>
                  <td><?= e($c['farm_name']) ?> <span class="text-muted small">/ <?= e($c['plot_name']) ?></span></td>
                  <td><?= e($c['season'] ?: '-') ?></td>
                  <td><?php render_status_badge($c['status']); ?></td>
                  <td><?= format_date($c['start_date']) ?></td>
                  <td><?= $c['budget'] !== null ? format_money((float) $c['budget']) : '-' ?></td>
                  <td class="text-end">
                    <a href="<?= base_url('org-admin/crops/view.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
