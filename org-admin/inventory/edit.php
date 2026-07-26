<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$categoryOptions = ['Fertilizer', 'Seeds', 'Tools', 'Pesticides', 'Feed', 'Other'];

$itemId = (int) clean_int($_GET['id'] ?? 0);
$item = tenant_find('inventory_items', $orgId, $itemId);

if (!$item) {
    session_flash('error', 'Inventory item not found.');
    redirect('org-admin/inventory/index.php');
}

$errors = [];
$input = $item;

if (is_post() && csrf_verify()) {
    $input = array_merge($input, [
        'category' => clean_string($_POST['category'] ?? ''),
        'name' => clean_string($_POST['name'] ?? ''),
        'sku' => clean_string($_POST['sku'] ?? ''),
        'unit' => clean_string($_POST['unit'] ?? ''),
        'reorder_level' => $_POST['reorder_level'] ?? '',
        'supplier_id' => $_POST['supplier_id'] ?? '',
        'purchase_price' => $_POST['purchase_price'] ?? '',
        'expiry_date' => $_POST['expiry_date'] ?? '',
        'storage_location' => clean_string($_POST['storage_location'] ?? ''),
        'description' => clean_string($_POST['description'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
    ]);

    $errors = validate($input, [
        'category' => 'required|max:100',
        'name' => 'required|max:255',
        'reorder_level' => 'numeric',
        'status' => 'in:active,inactive,discontinued',
    ]);

    if (!$errors) {
        $supplierId = clean_int($input['supplier_id']);
        if ($supplierId && !tenant_find('suppliers', $orgId, $supplierId)) {
            $supplierId = null;
        }

        tenant_update('inventory_items', $orgId, $itemId, [
            'category' => $input['category'],
            'name' => $input['name'],
            'sku' => $input['sku'] ?: null,
            'unit' => $input['unit'] ?: null,
            'reorder_level' => clean_float($input['reorder_level']),
            'supplier_id' => $supplierId,
            'purchase_price' => clean_float($input['purchase_price']),
            'expiry_date' => $input['expiry_date'] ?: null,
            'storage_location' => $input['storage_location'] ?: null,
            'description' => $input['description'] ?: null,
            'status' => $input['status'],
        ]);
        audit_log($orgId, $currentUser['id'], 'update', 'inventory_items', $itemId, $item, $input);
        session_flash('success', 'Inventory item updated.');
        redirect('org-admin/inventory/index.php');
    }
}

$GLOBALS['_page_errors'] = $errors;
$suppliers = tenant_all('suppliers', $orgId, "AND status = 'active' ORDER BY name");

render_header(['title' => 'Edit Inventory Item', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:800px;">
        <h5 class="mb-3">Edit Inventory Item</h5>
        <p class="text-muted small">Current quantity: <strong><?= number_format((float) $item['quantity'], 2) ?></strong> <?= e($item['unit'] ?: '') ?> — use Stock In/Out to change quantity.</p>
        <form method="post">
          <?= csrf_field() ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Category</label>
              <select name="category" class="form-select" required>
                <?php foreach ($categoryOptions as $opt): ?>
                  <option value="<?= e($opt) ?>" <?= $input['category'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
                <?php if (!in_array($input['category'], $categoryOptions, true) && $input['category'] !== ''): ?>
                  <option value="<?= e($input['category']) ?>" selected><?= e($input['category']) ?></option>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Item Name</label>
              <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">SKU</label>
              <input type="text" name="sku" class="form-control" value="<?= e($input['sku']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Unit</label>
              <input type="text" name="unit" class="form-control" value="<?= e($input['unit']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Supplier</label>
              <select name="supplier_id" class="form-select">
                <option value="">None</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= (string) $input['supplier_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Reorder Level</label>
              <input type="number" step="0.01" name="reorder_level" class="form-control" value="<?= e((string) $input['reorder_level']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Purchase Price</label>
              <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= e((string) $input['purchase_price']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?= $input['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $input['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="discontinued" <?= $input['status'] === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Expiry Date</label>
              <input type="date" name="expiry_date" class="form-control" value="<?= e((string) $input['expiry_date']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Storage Location</label>
              <input type="text" name="storage_location" class="form-control" value="<?= e($input['storage_location']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($input['description']) ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Update Item</button>
            <a href="<?= base_url('org-admin/inventory/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
