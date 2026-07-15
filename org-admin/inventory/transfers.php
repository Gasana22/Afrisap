<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];

if (is_post() && csrf_verify()) {
    $itemId = clean_int($_POST['item_id'] ?? 0);
    $item = tenant_find('inventory_items', $orgId, $itemId);
    $quantity = $_POST['quantity'] ?? '';
    $fromLocation = clean_string($_POST['from_location'] ?? '');
    $toLocation = clean_string($_POST['to_location'] ?? '');
    $notes = clean_string($_POST['notes'] ?? '');

    if (!$item) {
        $errors['item_id'] = 'Please select a valid inventory item.';
    }
    $errors = array_merge($errors, validate(
        ['quantity' => $quantity, 'to_location' => $toLocation],
        ['quantity' => 'required|numeric', 'to_location' => 'required|max:255']
    ));

    if (!$errors && (float) $quantity <= 0) {
        $errors['quantity'] = 'Quantity must be greater than zero.';
    }

    if (!$errors && $item && (float) $quantity > (float) $item['quantity']) {
        $errors['quantity'] = 'Not enough stock available to transfer. Current stock is ' . number_format((float) $item['quantity'], 2) . '.';
    }

    if (!$errors) {
        $qty = (float) $quantity;

        db_insert('inventory_transactions', [
            'item_id' => $item['id'],
            'type' => 'transfer',
            'quantity' => $qty,
            'reference_type' => 'manual',
            'from_location' => $fromLocation ?: null,
            'to_location' => $toLocation,
            'notes' => $notes ?: null,
            'created_by' => $currentUser['id'],
        ]);

        // Same stock, different location - simplification: move the item's storage_location.
        tenant_update('inventory_items', $orgId, $item['id'], [
            'storage_location' => $toLocation,
        ]);

        audit_log($orgId, $currentUser['id'], 'update', 'inventory_items', $item['id'], ['storage_location' => $item['storage_location']], ['storage_location' => $toLocation]);
        session_flash('success', 'Transfer recorded for ' . $item['name'] . '.');
        redirect('org-admin/inventory/transfers.php');
    }
}

$GLOBALS['_page_errors'] = $errors;
$items = tenant_all('inventory_items', $orgId, 'ORDER BY name');
$recentTransfers = db_all(
    "SELECT it.*, ii.name AS item_name, ii.unit FROM inventory_transactions it
     JOIN inventory_items ii ON ii.id = it.item_id
     WHERE ii.organization_id = :org_id AND it.type = 'transfer'
     ORDER BY it.created_at DESC LIMIT 25",
    ['org_id' => $orgId]
);

render_header(['title' => 'Inventory Transfers', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/inventory/index.php') ?>">Inventory</a></li>
        <li class="breadcrumb-item active">Transfers</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-5">
          <div class="content-card">
            <h6 class="mb-3">Record Transfer</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select" required>
                  <option value="">Select item</option>
                  <?php foreach ($items as $i): ?>
                    <option value="<?= $i['id'] ?>"><?= e($i['name']) ?> (<?= e($i['storage_location'] ?: 'no location') ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" step="0.01" name="quantity" class="form-control" required>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">From Location</label>
                  <input type="text" name="from_location" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">To Location</label>
                  <input type="text" name="to_location" class="form-control" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-left-right"></i> Record Transfer</button>
            </form>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card">
            <h6 class="mb-3">Recent Transfers</h6>
            <?php if (!$recentTransfers): ?>
              <?php render_empty_state('No transfers recorded yet.'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Date</th><th>Item</th><th>Quantity</th><th>From</th><th>To</th><th>Notes</th></tr></thead>
                  <tbody>
                  <?php foreach ($recentTransfers as $t): ?>
                    <tr>
                      <td><?= format_date($t['created_at'], 'd M Y H:i') ?></td>
                      <td><?= e($t['item_name']) ?></td>
                      <td><?= number_format((float) $t['quantity'], 2) ?> <?= e($t['unit'] ?: '') ?></td>
                      <td><?= e($t['from_location'] ?: '-') ?></td>
                      <td><?= e($t['to_location'] ?: '-') ?></td>
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
