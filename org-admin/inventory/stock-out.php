<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$itemId = clean_int($_GET['item_id'] ?? $_POST['item_id'] ?? 0);
$item = $itemId ? tenant_find('inventory_items', $orgId, $itemId) : null;

$errors = [];

if (is_post() && csrf_verify()) {
    $itemId = clean_int($_POST['item_id'] ?? 0);
    $item = tenant_find('inventory_items', $orgId, $itemId);

    $quantity = $_POST['quantity'] ?? '';
    $notes = clean_string($_POST['notes'] ?? '');
    $referenceType = clean_string($_POST['reference_type'] ?? '') ?: 'manual';

    if (!$item) {
        $errors['item_id'] = 'Please select a valid inventory item.';
    }

    $errors = array_merge($errors, validate(['quantity' => $quantity], ['quantity' => 'required|numeric']));

    if (!$errors && (float) $quantity <= 0) {
        $errors['quantity'] = 'Quantity must be greater than zero.';
    }

    if (!$errors && $item && (float) $quantity > (float) $item['quantity']) {
        $errors['quantity'] = 'Not enough stock available. Current stock is ' . number_format((float) $item['quantity'], 2) . ' ' . ($item['unit'] ?: '') . '.';
    }

    if (!$errors) {
        $qty = (float) $quantity;

        db_insert('inventory_transactions', [
            'item_id' => $item['id'],
            'type' => 'stock_out',
            'quantity' => $qty,
            'unit_cost' => null,
            'total_cost' => null,
            'reference_type' => $referenceType,
            'reference_id' => null,
            'notes' => $notes ?: null,
            'created_by' => $currentUser['id'],
        ]);

        tenant_update('inventory_items', $orgId, $item['id'], [
            'quantity' => (float) $item['quantity'] - $qty,
        ]);

        audit_log($orgId, $currentUser['id'], 'update', 'inventory_items', $item['id'], ['quantity' => $item['quantity']], ['quantity' => (float) $item['quantity'] - $qty, 'stock_out' => $qty]);
        session_flash('success', 'Stock usage recorded: -' . number_format($qty, 2) . ' ' . ($item['unit'] ?: '') . ' removed from ' . $item['name'] . '.');
        redirect('org-admin/inventory/stock-out.php?item_id=' . $item['id']);
    }
}

$GLOBALS['_page_errors'] = $errors;
$items = tenant_all('inventory_items', $orgId, 'ORDER BY name');
$recentTransactions = $item
    ? db_all("SELECT * FROM inventory_transactions WHERE item_id = :item_id AND type = 'stock_out' ORDER BY created_at DESC LIMIT 15", ['item_id' => $item['id']])
    : [];

render_header(['title' => 'Stock Out', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/inventory/index.php') ?>">Inventory</a></li>
        <li class="breadcrumb-item active">Stock Out</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-5">
          <div class="content-card">
            <h6 class="mb-3">Record Stock Usage</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select" required onchange="window.location='<?= base_url('org-admin/inventory/stock-out.php') ?>?item_id='+this.value">
                  <option value="">Select item</option>
                  <?php foreach ($items as $i): ?>
                    <option value="<?= $i['id'] ?>" <?= $item && $item['id'] == $i['id'] ? 'selected' : '' ?>><?= e($i['name']) ?> (<?= number_format((float) $i['quantity'], 2) ?> <?= e($i['unit'] ?: '') ?> in stock)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if ($item): ?>
                <p class="text-muted small">Current stock: <strong><?= number_format((float) $item['quantity'], 2) ?> <?= e($item['unit'] ?: '') ?></strong></p>
              <?php endif; ?>
              <div class="mb-3">
                <label class="form-label">Quantity Used</label>
                <input type="number" step="0.01" name="quantity" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Reference Type</label>
                <input type="text" name="reference_type" class="form-control" value="manual" placeholder="manual, crop_activity, ...">
              </div>
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
              </div>
              <button type="submit" class="btn btn-warning w-100" <?= $item ? '' : 'disabled' ?>><i class="bi bi-box-arrow-up"></i> Record Stock Out</button>
            </form>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card">
            <h6 class="mb-3">Recent Stock-Out Transactions <?= $item ? 'for ' . e($item['name']) : '' ?></h6>
            <?php if (!$item): ?>
              <?php render_empty_state('Select an item to view its recent stock-out history.'); ?>
            <?php elseif (!$recentTransactions): ?>
              <?php render_empty_state('No stock-out transactions recorded yet for this item.'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Date</th><th>Quantity</th><th>Reference</th><th>Notes</th></tr></thead>
                  <tbody>
                  <?php foreach ($recentTransactions as $t): ?>
                    <tr>
                      <td><?= format_date($t['created_at'], 'd M Y H:i') ?></td>
                      <td class="text-warning">-<?= number_format((float) $t['quantity'], 2) ?></td>
                      <td><?= e($t['reference_type'] ?: '-') ?></td>
                      <td><?= e($t['notes'] ?: '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
