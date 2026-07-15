<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];
$input = [
    'supplier_id' => '',
    'order_date' => date('Y-m-d'),
    'expected_delivery' => '',
    'notes' => '',
    'tax' => '0',
];
$lineItems = [];
for ($i = 0; $i < 5; $i++) {
    $lineItems[] = ['item_name' => '', 'quantity' => '', 'unit' => '', 'unit_price' => ''];
}

if (is_post() && csrf_verify()) {
    $input = [
        'supplier_id' => $_POST['supplier_id'] ?? '',
        'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
        'expected_delivery' => $_POST['expected_delivery'] ?? '',
        'notes' => clean_string($_POST['notes'] ?? ''),
        'tax' => $_POST['tax'] ?? '0',
    ];

    $postedItems = $_POST['items'] ?? [];
    $lineItems = [];
    $validLines = [];
    foreach ($postedItems as $row) {
        $name = clean_string($row['item_name'] ?? '');
        $qty = $row['quantity'] ?? '';
        $unit = clean_string($row['unit'] ?? '');
        $unitPrice = $row['unit_price'] ?? '';
        $lineItems[] = ['item_name' => $name, 'quantity' => $qty, 'unit' => $unit, 'unit_price' => $unitPrice];

        if ($name === '' && $qty === '') {
            continue; // skip empty rows
        }
        if ($name === '' || !is_numeric($qty) || (float) $qty <= 0) {
            $errors['items'] = 'Each line item needs a name and a quantity greater than zero.';
            continue;
        }
        $validLines[] = [
            'item_name' => $name,
            'quantity' => (float) $qty,
            'unit' => $unit ?: null,
            'unit_price' => $unitPrice !== '' ? (float) $unitPrice : 0,
        ];
    }
    while (count($lineItems) < 5) {
        $lineItems[] = ['item_name' => '', 'quantity' => '', 'unit' => '', 'unit_price' => ''];
    }

    $supplierId = clean_int($input['supplier_id']);
    $supplier = $supplierId ? tenant_find('suppliers', $orgId, $supplierId) : null;
    if (!$supplier) {
        $errors['supplier_id'] = 'Please select a valid supplier.';
    }

    $errors = array_merge($errors, validate($input, ['order_date' => 'required|date']));

    if (empty($validLines) && empty($errors['items'])) {
        $errors['items'] = 'Add at least one line item.';
    }

    if (!$errors) {
        $subtotal = 0.0;
        foreach ($validLines as $line) {
            $subtotal += $line['quantity'] * $line['unit_price'];
        }
        $tax = (float) ($input['tax'] ?: 0);
        $total = $subtotal + $tax;

        $orderId = tenant_insert('purchase_orders', $orgId, [
            'supplier_id' => $supplier['id'],
            'order_number' => generate_order_number(),
            'order_date' => $input['order_date'],
            'expected_delivery' => $input['expected_delivery'] ?: null,
            'status' => 'draft',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total_amount' => $total,
            'payment_status' => 'pending',
            'notes' => $input['notes'] ?: null,
            'created_by' => $currentUser['id'],
        ]);

        foreach ($validLines as $line) {
            db_insert('purchase_order_items', [
                'purchase_order_id' => $orderId,
                'item_id' => null,
                'item_name' => $line['item_name'],
                'quantity' => $line['quantity'],
                'unit' => $line['unit'],
                'unit_price' => $line['unit_price'],
                'line_total' => $line['quantity'] * $line['unit_price'],
                'quantity_received' => 0,
            ]);
        }

        audit_log($orgId, $currentUser['id'], 'create', 'purchase_orders', $orderId, null, ['supplier_id' => $supplier['id'], 'total_amount' => $total, 'items' => count($validLines)]);
        session_flash('success', 'Purchase order created.');
        redirect('org-admin/procurement/orders.php');
    }
}

$GLOBALS['_page_errors'] = $errors;
$suppliers = tenant_all('suppliers', $orgId, "AND status = 'active' ORDER BY name");

render_header(['title' => 'Create Purchase Order', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/procurement/orders.php') ?>">Purchase Orders</a></li>
        <li class="breadcrumb-item active">New Order</li>
      </ol></nav>

      <div class="content-card" style="max-width:960px;">
        <h5 class="mb-3">Create Purchase Order</h5>
        <form method="post" id="poForm">
          <?= csrf_field() ?>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Supplier</label>
              <select name="supplier_id" class="form-select" required>
                <option value="">Select supplier</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= (string) $input['supplier_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Order Date</label>
              <input type="date" name="order_date" class="form-control" required value="<?= e($input['order_date']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Expected Delivery</label>
              <input type="date" name="expected_delivery" class="form-control" value="<?= e($input['expected_delivery']) ?>">
            </div>
          </div>

          <h6 class="mt-4 mb-2">Line Items</h6>
          <div class="table-responsive">
            <table class="table-app" id="lineItemsTable">
              <thead><tr><th>Item Name</th><th>Quantity</th><th>Unit</th><th>Unit Price</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($lineItems as $idx => $line): ?>
                  <tr>
                    <td><input type="text" name="items[<?= $idx ?>][item_name]" class="form-control form-control-sm" value="<?= e($line['item_name']) ?>"></td>
                    <td><input type="number" step="0.01" name="items[<?= $idx ?>][quantity]" class="form-control form-control-sm" value="<?= e((string) $line['quantity']) ?>"></td>
                    <td><input type="text" name="items[<?= $idx ?>][unit]" class="form-control form-control-sm" value="<?= e($line['unit']) ?>"></td>
                    <td><input type="number" step="0.01" name="items[<?= $idx ?>][unit_price]" class="form-control form-control-sm" value="<?= e((string) $line['unit_price']) ?>"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <button type="button" id="addRowBtn" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-plus"></i> Add Row</button>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Tax</label>
              <input type="number" step="0.01" name="tax" class="form-control" value="<?= e($input['tax']) ?>">
            </div>
          </div>
          <p class="text-muted small">Subtotal and total are calculated server-side from the line items you enter, so you don't need to compute them yourself.</p>

          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= e($input['notes']) ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Create Order</button>
            <a href="<?= base_url('org-admin/procurement/orders.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<script>
document.getElementById('addRowBtn').addEventListener('click', function () {
  var tbody = document.querySelector('#lineItemsTable tbody');
  var idx = tbody.querySelectorAll('tr').length;
  var tr = document.createElement('tr');
  tr.innerHTML =
    '<td><input type="text" name="items[' + idx + '][item_name]" class="form-control form-control-sm"></td>' +
    '<td><input type="number" step="0.01" name="items[' + idx + '][quantity]" class="form-control form-control-sm"></td>' +
    '<td><input type="text" name="items[' + idx + '][unit]" class="form-control form-control-sm"></td>' +
    '<td><input type="number" step="0.01" name="items[' + idx + '][unit_price]" class="form-control form-control-sm"></td>' +
    '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>';
  tbody.appendChild(tr);
});
document.querySelector('#lineItemsTable tbody').addEventListener('click', function (e) {
  var btn = e.target.closest('.remove-row');
  if (btn) {
    btn.closest('tr').remove();
  }
});
</script>
<?php render_footer(['context' => 'org-admin']); ?>
