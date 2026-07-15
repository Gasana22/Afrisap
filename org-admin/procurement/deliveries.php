<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

if (is_post() && csrf_verify()) {
    $poId = clean_int($_POST['purchase_order_id'] ?? 0);
    $po = tenant_find('purchase_orders', $orgId, $poId);

    if (!$po) {
        session_flash('error', 'Purchase order not found.');
        redirect('org-admin/procurement/deliveries.php');
    }

    $received = $_POST['received'] ?? []; // [poi_id => qty]
    $lineItems = db_all('SELECT * FROM purchase_order_items WHERE purchase_order_id = :id', ['id' => $poId]);
    $lineById = [];
    foreach ($lineItems as $li) {
        $lineById[$li['id']] = $li;
    }

    foreach ($received as $lineId => $qtyReceivedInput) {
        $lineId = (int) $lineId;
        if (!isset($lineById[$lineId]) || $qtyReceivedInput === '' || !is_numeric($qtyReceivedInput)) {
            continue;
        }
        $line = $lineById[$lineId];
        $qty = (float) $qtyReceivedInput;
        if ($qty <= 0) {
            continue;
        }

        $remaining = (float) $line['quantity'] - (float) $line['quantity_received'];
        $qty = min($qty, $remaining);
        if ($qty <= 0) {
            continue;
        }

        $newReceived = (float) $line['quantity_received'] + $qty;
        db_update('purchase_order_items', ['quantity_received' => $newReceived], 'id = :id', ['id' => $lineId]);

        // Integration point: if this line is linked to an inventory item, bump stock.
        if (!empty($line['item_id'])) {
            $invItem = tenant_find('inventory_items', $orgId, (int) $line['item_id']);
            if ($invItem) {
                tenant_update('inventory_items', $orgId, $invItem['id'], [
                    'quantity' => (float) $invItem['quantity'] + $qty,
                ]);
                db_insert('inventory_transactions', [
                    'item_id' => $invItem['id'],
                    'type' => 'stock_in',
                    'quantity' => $qty,
                    'unit_cost' => $line['unit_price'],
                    'total_cost' => $qty * (float) $line['unit_price'],
                    'reference_type' => 'purchase_order',
                    'reference_id' => $poId,
                    'notes' => 'Received against PO ' . $po['order_number'],
                    'created_by' => $currentUser['id'],
                ]);
            }
        }
    }

    // Recompute PO status from line items.
    $freshItems = db_all('SELECT * FROM purchase_order_items WHERE purchase_order_id = :id', ['id' => $poId]);
    $allReceived = true;
    $anyReceived = false;
    foreach ($freshItems as $li) {
        if ((float) $li['quantity_received'] < (float) $li['quantity']) {
            $allReceived = false;
        }
        if ((float) $li['quantity_received'] > 0) {
            $anyReceived = true;
        }
    }
    $newStatus = $allReceived ? 'received' : ($anyReceived ? 'partial' : $po['status']);

    if ($newStatus !== $po['status']) {
        tenant_update('purchase_orders', $orgId, $poId, ['status' => $newStatus]);
    }

    audit_log($orgId, $currentUser['id'], 'update', 'purchase_orders', $poId, ['status' => $po['status']], ['status' => $newStatus]);
    session_flash('success', 'Delivery recorded for order ' . $po['order_number'] . '.');
    redirect('org-admin/procurement/deliveries.php');
}

$pendingOrders = db_all(
    "SELECT po.*, s.name AS supplier_name FROM purchase_orders po
     JOIN suppliers s ON s.id = po.supplier_id
     WHERE po.organization_id = :organization_id AND po.status IN ('sent', 'partial')
     ORDER BY po.expected_delivery ASC",
    ['organization_id' => $orgId]
);
foreach ($pendingOrders as &$order) {
    $order['items'] = db_all('SELECT * FROM purchase_order_items WHERE purchase_order_id = :id', ['id' => $order['id']]);
}
unset($order);

render_header(['title' => 'Awaiting Deliveries', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/procurement/orders.php') ?>">Purchase Orders</a></li>
        <li class="breadcrumb-item active">Deliveries</li>
      </ol></nav>

      <div class="mb-3">
        <h4 class="mb-0">Awaiting Delivery</h4>
        <p class="text-muted small mb-0">Purchase orders that have been sent to suppliers and are not yet fully received</p>
      </div>

      <?php if (!$pendingOrders): ?>
        <div class="content-card"><?php render_empty_state('No orders currently awaiting delivery.', 'bi-truck'); ?></div>
      <?php else: ?>
        <?php foreach ($pendingOrders as $order): ?>
          <div class="content-card mb-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h6 class="mb-0"><?= e($order['order_number']) ?> &mdash; <?= e($order['supplier_name']) ?></h6>
                <p class="text-muted small mb-0">Expected: <?= format_date($order['expected_delivery']) ?></p>
              </div>
              <?php render_status_badge($order['status']); ?>
            </div>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="purchase_order_id" value="<?= $order['id'] ?>">
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Item</th><th>Ordered</th><th>Received So Far</th><th>Remaining</th><th style="width:160px;">Receive Now</th></tr></thead>
                  <tbody>
                    <?php foreach ($order['items'] as $li): ?>
                      <?php $remaining = (float) $li['quantity'] - (float) $li['quantity_received']; ?>
                      <tr>
                        <td><?= e($li['item_name']) ?></td>
                        <td><?= number_format((float) $li['quantity'], 2) ?> <?= e($li['unit'] ?: '') ?></td>
                        <td><?= number_format((float) $li['quantity_received'], 2) ?></td>
                        <td><?= number_format($remaining, 2) ?></td>
                        <td>
                          <?php if ($remaining > 0): ?>
                            <input type="number" step="0.01" min="0" max="<?= e((string) $remaining) ?>" name="received[<?= $li['id'] ?>]" class="form-control form-control-sm" placeholder="0">
                          <?php else: ?>
                            <span class="badge badge-success">Complete</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-circle"></i> Mark Items Received</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
