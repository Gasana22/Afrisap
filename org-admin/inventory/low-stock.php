<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$items = tenant_all(
    'inventory_items',
    $orgId,
    'AND reorder_level IS NOT NULL AND quantity <= reorder_level ORDER BY (quantity - reorder_level) ASC'
);

render_header(['title' => 'Low Stock Alerts', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/inventory/index.php') ?>">Inventory</a></li>
        <li class="breadcrumb-item active">Low Stock</li>
      </ol></nav>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0"><i class="bi bi-exclamation-triangle text-danger"></i> Low Stock Alerts</h4>
          <p class="text-muted small mb-0">Items at or below their reorder level, ordered by most critical first</p>
        </div>
      </div>

      <div class="content-card">
        <?php if (!$items): ?>
          <?php render_empty_state('Nothing is low on stock right now. Great job!', 'bi-check-circle'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Quantity</th>
                  <th>Reorder Level</th>
                  <th>Shortfall</th>
                  <th>Unit</th>
                  <th>Storage</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <?php $shortfall = (float) $item['reorder_level'] - (float) $item['quantity']; ?>
                  <tr>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['category']) ?></td>
                    <td class="text-danger fw-bold"><?= number_format((float) $item['quantity'], 2) ?></td>
                    <td><?= number_format((float) $item['reorder_level'], 2) ?></td>
                    <td class="text-danger"><?= number_format($shortfall, 2) ?></td>
                    <td><?= e($item['unit'] ?: '-') ?></td>
                    <td><?= e($item['storage_location'] ?: '-') ?></td>
                    <td class="text-end">
                      <a href="<?= base_url('org-admin/inventory/stock-in.php?item_id=' . $item['id']) ?>" class="btn btn-sm btn-success"><i class="bi bi-box-arrow-in-down"></i> Stock In</a>
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
