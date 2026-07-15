<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$cropCycleId = clean_int($_GET['crop_cycle_id'] ?? 0);
$cycle = tenant_find('crop_cycles', $orgId, $cropCycleId);

if (!$cycle) {
    session_flash('error', 'Crop cycle not found.');
    redirect('org-admin/crops/index.php');
}

$ITEM_TYPES = ['seeds', 'fertilizers', 'tools', 'pesticides', 'other'];

$errors = [];
$input = [
    'supplier_id' => '', 'item_type' => 'seeds', 'item_name' => '', 'quantity' => '', 'unit' => '',
    'cost' => '', 'purchase_date' => '', 'batch_number' => '', 'expiry_date' => '',
];

if (is_post() && csrf_verify()) {
    $input = [
        'supplier_id' => $_POST['supplier_id'] ?? '',
        'item_type' => $_POST['item_type'] ?? '',
        'item_name' => clean_string($_POST['item_name'] ?? ''),
        'quantity' => $_POST['quantity'] ?? '',
        'unit' => clean_string($_POST['unit'] ?? ''),
        'cost' => $_POST['cost'] ?? '',
        'purchase_date' => $_POST['purchase_date'] ?? '',
        'batch_number' => clean_string($_POST['batch_number'] ?? ''),
        'expiry_date' => $_POST['expiry_date'] ?? '',
    ];

    $errors = validate($input, [
        'item_type' => 'required|in:' . implode(',', $ITEM_TYPES),
        'item_name' => 'required|max:255',
        'quantity' => 'numeric',
        'cost' => 'numeric',
        'purchase_date' => 'required|date',
        'expiry_date' => 'date',
    ]);

    $supplierId = clean_int($input['supplier_id']);
    if ($supplierId) {
        $supplier = tenant_find('suppliers', $orgId, $supplierId);
        if (!$supplier) {
            $errors['supplier_id'] = 'Please choose a valid supplier.';
        }
    } else {
        $supplierId = null;
    }

    if (!$errors) {
        $id = db_insert('crop_procurements', [
            'crop_cycle_id' => $cropCycleId,
            'supplier_id' => $supplierId,
            'item_type' => $input['item_type'],
            'item_name' => $input['item_name'],
            'quantity' => $input['quantity'] !== '' ? clean_float($input['quantity']) : null,
            'unit' => $input['unit'] ?: null,
            'cost' => $input['cost'] !== '' ? clean_float($input['cost']) : null,
            'purchase_date' => $input['purchase_date'],
            'batch_number' => $input['batch_number'] ?: null,
            'expiry_date' => $input['expiry_date'] ?: null,
            'created_by' => $currentUser['id'],
        ]);
        audit_log($orgId, $currentUser['id'], 'create', 'crop_procurements', $id, null, $input);
        session_flash('success', 'Procurement recorded.');
        redirect('org-admin/crops/procurement.php?crop_cycle_id=' . $cropCycleId);
    }
}

$GLOBALS['_page_errors'] = $errors;

$suppliers = tenant_all('suppliers', $orgId, "AND status = 'active' ORDER BY name");
$procurements = db_all(
    'SELECT cp.*, s.name AS supplier_name FROM crop_procurements cp
     LEFT JOIN suppliers s ON s.id = cp.supplier_id
     WHERE cp.crop_cycle_id = :id ORDER BY cp.purchase_date DESC, cp.created_at DESC',
    ['id' => $cropCycleId]
);

render_header(['title' => 'Procurement - ' . $cycle['crop_batch_id'], 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/crops/index.php') ?>">Crop Management</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/crops/view.php?id=' . $cropCycleId) ?>"><?= e($cycle['crop_batch_id']) ?></a></li>
        <li class="breadcrumb-item active">Procurement</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card">
            <h6 class="mb-3">Record Procurement</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label small">Supplier</label>
                <select name="supplier_id" class="form-select">
                  <?php if (!$suppliers): ?>
                    <option value="">No suppliers yet</option>
                  <?php else: ?>
                    <option value="">Select supplier (optional)</option>
                    <?php foreach ($suppliers as $s): ?>
                      <option value="<?= $s['id'] ?>" <?= (string) $input['supplier_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label small">Item Type</label>
                <select name="item_type" class="form-select">
                  <?php foreach ($ITEM_TYPES as $type): ?>
                    <option value="<?= e($type) ?>" <?= $input['item_type'] === $type ? 'selected' : '' ?>><?= e(humanize($type)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label small">Item Name</label>
                <input type="text" name="item_name" class="form-control" required value="<?= e($input['item_name']) ?>">
              </div>
              <div class="row">
                <div class="col-6 mb-2">
                  <label class="form-label small">Quantity</label>
                  <input type="number" step="0.01" name="quantity" class="form-control" value="<?= e((string) $input['quantity']) ?>">
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label small">Unit</label>
                  <input type="text" name="unit" class="form-control" value="<?= e($input['unit']) ?>" placeholder="kg, bags...">
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small">Cost</label>
                <input type="number" step="0.01" name="cost" class="form-control" value="<?= e((string) $input['cost']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Purchase Date</label>
                <input type="date" name="purchase_date" class="form-control" required value="<?= e($input['purchase_date']) ?>">
              </div>
              <div class="row">
                <div class="col-6 mb-2">
                  <label class="form-label small">Batch Number</label>
                  <input type="text" name="batch_number" class="form-control" value="<?= e($input['batch_number']) ?>">
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label small">Expiry Date</label>
                  <input type="date" name="expiry_date" class="form-control" value="<?= e($input['expiry_date']) ?>">
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100">Save Procurement</button>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="content-card">
            <h6 class="mb-3">Procurement History</h6>
            <?php if (!$procurements): ?>
              <?php render_empty_state('No procurements recorded yet.'); ?>
            <?php else: ?>
              <table class="table-app">
                <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Supplier</th><th>Qty</th><th>Cost</th><th>Batch</th></tr></thead>
                <tbody>
                <?php foreach ($procurements as $p): ?>
                  <tr>
                    <td><?= format_date($p['purchase_date']) ?></td>
                    <td><?= e($p['item_name']) ?></td>
                    <td><?php render_status_badge($p['item_type']); ?></td>
                    <td><?= e($p['supplier_name'] ?: '-') ?></td>
                    <td><?= $p['quantity'] !== null ? number_format((float) $p['quantity'], 2) . ' ' . e($p['unit'] ?: '') : '-' ?></td>
                    <td><?= $p['cost'] !== null ? format_money((float) $p['cost']) : '-' ?></td>
                    <td><?= e($p['batch_number'] ?: '-') ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
