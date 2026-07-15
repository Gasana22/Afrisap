<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$categoryOptions = ['Fertilizer', 'Seeds', 'Tools', 'Pesticides', 'Feed', 'Other'];

$errors = [];
$input = [
    'category' => '',
    'name' => '',
    'sku' => '',
    'unit' => '',
    'quantity' => '0',
    'reorder_level' => '',
    'supplier_id' => '',
    'purchase_price' => '',
    'expiry_date' => '',
    'storage_location' => '',
    'description' => '',
];

if (is_post() && csrf_verify()) {
    $input = [
        'category' => clean_string($_POST['category'] ?? ''),
        'name' => clean_string($_POST['name'] ?? ''),
        'sku' => clean_string($_POST['sku'] ?? ''),
        'unit' => clean_string($_POST['unit'] ?? ''),
        'quantity' => $_POST['quantity'] ?? '0',
        'reorder_level' => $_POST['reorder_level'] ?? '',
        'supplier_id' => $_POST['supplier_id'] ?? '',
        'purchase_price' => $_POST['purchase_price'] ?? '',
        'expiry_date' => $_POST['expiry_date'] ?? '',
        'storage_location' => clean_string($_POST['storage_location'] ?? ''),
        'description' => clean_string($_POST['description'] ?? ''),
    ];

    $errors = validate($input, [
        'category' => 'required|max:100',
        'name' => 'required|max:255',
        'quantity' => 'numeric',
        'reorder_level' => 'numeric',
    ]);

    if (!$errors) {
        $supplierId = clean_int($input['supplier_id']);
        if ($supplierId && !tenant_find('suppliers', $orgId, $supplierId)) {
            $supplierId = null;
        }

        $id = tenant_insert('inventory_items', $orgId, [
            'category' => $input['category'],
            'name' => $input['name'],
            'sku' => $input['sku'] ?: null,
            'unit' => $input['unit'] ?: null,
            'quantity' => clean_float($input['quantity']) ?? 0,
            'reorder_level' => clean_float($input['reorder_level']),
            'supplier_id' => $supplierId,
            'purchase_price' => clean_float($input['purchase_price']),
            'expiry_date' => $input['expiry_date'] ?: null,
            'storage_location' => $input['storage_location'] ?: null,
            'description' => $input['description'] ?: null,
            'created_by' => $currentUser['id'],
        ]);
        audit_log($orgId, $currentUser['id'], 'create', 'inventory_items', $id, null, $input);
        session_flash('success', 'Inventory item added.');
        redirect('org-admin/inventory/index.php');
    }
}

$GLOBALS['_page_errors'] = $errors;
$suppliers = tenant_all('suppliers', $orgId, "AND status = 'active' ORDER BY name");

render_header(['title' => 'Add Inventory Item', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:800px;">
        <h5 class="mb-3">Add Inventory Item</h5>
        <form method="post">
          <?= csrf_field() ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Category</label>
              <select name="category" class="form-select" required>
                <option value="">Select category</option>
                <?php foreach ($categoryOptions as $opt): ?>
                  <option value="<?= e($opt) ?>" <?= $input['category'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
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
              <input type="text" name="unit" class="form-control" placeholder="kg, bag, litre..." value="<?= e($input['unit']) ?>">
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
              <label class="form-label">Initial Quantity</label>
              <input type="number" step="0.01" name="quantity" class="form-control" value="<?= e((string) $input['quantity']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Reorder Level</label>
              <input type="number" step="0.01" name="reorder_level" class="form-control" value="<?= e((string) $input['reorder_level']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Purchase Price</label>
              <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= e((string) $input['purchase_price']) ?>">
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
            <button type="submit" class="btn btn-primary">Save Item</button>
            <a href="<?= base_url('org-admin/inventory/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
