<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$statusFilter = clean_string($_GET['status'] ?? '');
$paymentStatusFilter = clean_string($_GET['payment_status'] ?? '');
$viewId = clean_int($_GET['view'] ?? 0);

$extraSql = '';
$params = [];
if ($statusFilter !== '') {
    $extraSql .= ' AND po.status = :status';
    $params['status'] = $statusFilter;
}
if ($paymentStatusFilter !== '') {
    $extraSql .= ' AND po.payment_status = :payment_status';
    $params['payment_status'] = $paymentStatusFilter;
}

$orders = db_all(
    "SELECT po.*, s.name AS supplier_name FROM purchase_orders po
     JOIN suppliers s ON s.id = po.supplier_id
     WHERE po.organization_id = :organization_id $extraSql
     ORDER BY po.created_at DESC",
    array_merge(['organization_id' => $orgId], $params)
);

$viewOrder = null;
$viewItems = [];
if ($viewId) {
    $viewOrder = tenant_find('purchase_orders', $orgId, $viewId);
    if ($viewOrder) {
        $viewOrder['supplier_name'] = db_value('SELECT name FROM suppliers WHERE id = :id', ['id' => $viewOrder['supplier_id']]);
        $viewItems = db_all('SELECT * FROM purchase_order_items WHERE purchase_order_id = :id', ['id' => $viewId]);
    }
}

render_header(['title' => 'Purchase Orders', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Purchase Orders</h4>
          <p class="text-muted small mb-0">Track orders placed with your suppliers</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('org-admin/procurement/deliveries.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-truck"></i> Deliveries</a>
          <a href="<?= base_url('org-admin/procurement/create-order.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Order</a>
        </div>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
              <option value="">All Statuses</option>
              <?php foreach (['draft', 'sent', 'partial', 'received', 'canceled'] as $st): ?>
                <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e(humanize($st)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small">Payment Status</label>
            <select name="payment_status" class="form-select">
              <option value="">All Payment Statuses</option>
              <?php foreach (['pending', 'partial', 'paid'] as $st): ?>
                <option value="<?= $st ?>" <?= $paymentStatusFilter === $st ? 'selected' : '' ?>><?= e(humanize($st)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/procurement/orders.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$orders): ?>
          <?php render_empty_state('No purchase orders yet. Create your first order.', 'bi-cart'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Supplier</th>
                  <th>Order Date</th>
                  <th>Expected Delivery</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td><?= e($o['order_number']) ?></td>
                    <td><?= e($o['supplier_name']) ?></td>
                    <td><?= format_date($o['order_date']) ?></td>
                    <td><?= format_date($o['expected_delivery']) ?></td>
                    <td><?= format_money((float) $o['total_amount']) ?></td>
                    <td><?php render_status_badge($o['status']); ?></td>
                    <td><?php render_status_badge($o['payment_status']); ?></td>
                    <td class="text-end">
                      <a href="<?= base_url('org-admin/procurement/orders.php?view=' . $o['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($viewOrder): ?>
        <div class="content-card mt-3" id="orderDetail">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h5 class="mb-0">Order <?= e($viewOrder['order_number']) ?></h5>
              <p class="text-muted small mb-0">Supplier: <?= e($viewOrder['supplier_name']) ?></p>
            </div>
            <div class="text-end">
              <?php render_status_badge($viewOrder['status']); ?>
              <?php render_status_badge($viewOrder['payment_status']); ?>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-3"><strong>Order Date:</strong> <?= format_date($viewOrder['order_date']) ?></div>
            <div class="col-md-3"><strong>Expected Delivery:</strong> <?= format_date($viewOrder['expected_delivery']) ?></div>
            <div class="col-md-3"><strong>Subtotal:</strong> <?= format_money((float) $viewOrder['subtotal']) ?></div>
            <div class="col-md-3"><strong>Total:</strong> <?= format_money((float) $viewOrder['total_amount']) ?></div>
          </div>
          <?php if ($viewOrder['notes']): ?><p class="text-muted"><?= e($viewOrder['notes']) ?></p><?php endif; ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead><tr><th>Item</th><th>Quantity</th><th>Unit</th><th>Unit Price</th><th>Line Total</th><th>Received</th></tr></thead>
              <tbody>
                <?php foreach ($viewItems as $it): ?>
                  <tr>
                    <td><?= e($it['item_name']) ?></td>
                    <td><?= number_format((float) $it['quantity'], 2) ?></td>
                    <td><?= e($it['unit'] ?: '-') ?></td>
                    <td><?= format_money((float) $it['unit_price']) ?></td>
                    <td><?= format_money((float) $it['line_total']) ?></td>
                    <td><?= number_format((float) $it['quantity_received'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
