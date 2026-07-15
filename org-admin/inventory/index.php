<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$category = clean_string($_GET['category'] ?? '');
$search = clean_string($_GET['search'] ?? '');

$extraSql = '';
$params = [];
if ($category !== '') {
    $extraSql .= ' AND category = :category';
    $params['category'] = $category;
}
if ($search !== '') {
    $extraSql .= ' AND (name LIKE :search OR sku LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
$extraSql .= ' ORDER BY name ASC';

$items = tenant_all('inventory_items', $orgId, $extraSql, $params);
$categories = db_all('SELECT DISTINCT category FROM inventory_items WHERE organization_id = :org_id ORDER BY category', ['org_id' => $orgId]);

render_header(['title' => 'Inventory', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Inventory</h4>
          <p class="text-muted small mb-0">Track stock levels across all your farm supplies</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('org-admin/inventory/low-stock.php') ?>" class="btn btn-outline-danger"><i class="bi bi-exclamation-triangle"></i> Low Stock</a>
          <a href="<?= base_url('org-admin/inventory/transfers.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-right"></i> Transfers</a>
          <a href="<?= base_url('org-admin/inventory/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Item</a>
        </div>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name or SKU" value="<?= e($search) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small">Category</label>
            <select name="category" class="form-select">
              <option value="">All Categories</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/inventory/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$items): ?>
          <?php render_empty_state('No inventory items found. Add your first item to get started.', 'bi-box-seam'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Category</th>
                  <th>SKU</th>
                  <th>Quantity</th>
                  <th>Reorder Level</th>
                  <th>Unit</th>
                  <th>Storage</th>
                  <th>Expiry</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <?php $low = (float) $item['quantity'] <= (float) $item['reorder_level']; ?>
                  <tr>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['category']) ?></td>
                    <td><?= e($item['sku'] ?: '-') ?></td>
                    <td class="<?= $low ? 'text-danger fw-bold' : '' ?>">
                      <?= number_format((float) $item['quantity'], 2) ?>
                      <?php if ($low): ?><i class="bi bi-exclamation-triangle-fill" title="At or below reorder level"></i><?php endif; ?>
                    </td>
                    <td><?= number_format((float) $item['reorder_level'], 2) ?></td>
                    <td><?= e($item['unit'] ?: '-') ?></td>
                    <td><?= e($item['storage_location'] ?: '-') ?></td>
                    <td><?= format_date($item['expiry_date']) ?></td>
                    <td><?php render_status_badge($item['status']); ?></td>
                    <td class="text-end">
                      <a href="<?= base_url('org-admin/inventory/stock-in.php?item_id=' . $item['id']) ?>" class="btn btn-sm btn-outline-success" title="Stock In"><i class="bi bi-box-arrow-in-down"></i></a>
                      <a href="<?= base_url('org-admin/inventory/stock-out.php?item_id=' . $item['id']) ?>" class="btn btn-sm btn-outline-warning" title="Stock Out"><i class="bi bi-box-arrow-up"></i></a>
                      <a href="<?= base_url('org-admin/inventory/edit.php?id=' . $item['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
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
<?php render_footer(['context' => 'org-admin']); ?>
