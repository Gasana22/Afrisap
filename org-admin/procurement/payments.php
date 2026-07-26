<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];

if (is_post() && csrf_verify()) {
    $input = [
        'supplier_id' => $_POST['supplier_id'] ?? '',
        'purchase_order_id' => $_POST['purchase_order_id'] ?? '',
        'amount' => $_POST['amount'] ?? '',
        'payment_date' => $_POST['payment_date'] ?? '',
        'payment_method' => clean_string($_POST['payment_method'] ?? ''),
        'reference' => clean_string($_POST['reference'] ?? ''),
        'notes' => clean_string($_POST['notes'] ?? ''),
    ];

    $supplierId = clean_int($input['supplier_id']);
    $supplier = $supplierId ? tenant_find('suppliers', $orgId, $supplierId) : null;
    if (!$supplier) {
        $errors['supplier_id'] = 'Please select a valid supplier.';
    }

    $errors = array_merge($errors, validate($input, [
        'amount' => 'required|numeric',
        'payment_date' => 'required|date',
    ]));

    $poId = clean_int($input['purchase_order_id']);
    if ($poId) {
        $po = tenant_find('purchase_orders', $orgId, $poId);
        if (!$po || (int) $po['supplier_id'] !== $supplierId) {
            $errors['purchase_order_id'] = 'Selected order does not belong to this supplier.';
            $poId = null;
        }
    } else {
        $poId = null;
    }

    if (!$errors) {
        $paymentId = tenant_insert('supplier_payments', $orgId, [
            'supplier_id' => $supplier['id'],
            'purchase_order_id' => $poId,
            'amount' => (float) $input['amount'],
            'payment_date' => $input['payment_date'],
            'payment_method' => $input['payment_method'] ?: null,
            'reference' => $input['reference'] ?: null,
            'notes' => $input['notes'] ?: null,
            'created_by' => $currentUser['id'],
        ]);

        // Update the linked PO's payment_status based on total payments made against it.
        if ($poId) {
            $po = tenant_find('purchase_orders', $orgId, $poId);
            $totalPaid = (float) db_value(
                'SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE purchase_order_id = :po_id',
                ['po_id' => $poId]
            );
            $newPaymentStatus = 'pending';
            if ($totalPaid >= (float) $po['total_amount'] && (float) $po['total_amount'] > 0) {
                $newPaymentStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $newPaymentStatus = 'partial';
            }
            tenant_update('purchase_orders', $orgId, $poId, ['payment_status' => $newPaymentStatus]);
        }

        audit_log($orgId, $currentUser['id'], 'create', 'supplier_payments', $paymentId, null, $input);
        session_flash('success', 'Payment recorded.');
        redirect('org-admin/procurement/payments.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

$supplierFilter = (int) clean_int($_GET['supplier_id'] ?? 0);
$suppliers = tenant_all('suppliers', $orgId, 'ORDER BY name');

$paymentExtraSql = '';
$paymentParams = [];
if ($supplierFilter) {
    $paymentExtraSql = 'AND sp.supplier_id = :supplier_filter';
    $paymentParams['supplier_filter'] = $supplierFilter;
}

$payments = db_all(
    "SELECT sp.*, s.name AS supplier_name, po.order_number FROM supplier_payments sp
     JOIN suppliers s ON s.id = sp.supplier_id
     LEFT JOIN purchase_orders po ON po.id = sp.purchase_order_id
     WHERE sp.organization_id = :organization_id $paymentExtraSql
     ORDER BY sp.payment_date DESC, sp.created_at DESC",
    array_merge(['organization_id' => $orgId], $paymentParams)
);

// Orders per supplier for the create-payment form (client-side filtering via data attributes).
$allOrders = db_all(
    "SELECT id, order_number, supplier_id, total_amount FROM purchase_orders WHERE organization_id = :organization_id ORDER BY order_date DESC",
    ['organization_id' => $orgId]
);

render_header(['title' => 'Supplier Payments', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/procurement/suppliers.php') ?>">Procurement</a></li>
        <li class="breadcrumb-item active">Payments</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-5">
          <div class="content-card">
            <h6 class="mb-3">Record Payment</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" id="supplierSelect" class="form-select" required>
                  <option value="">Select supplier</option>
                  <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Purchase Order (optional)</label>
                <select name="purchase_order_id" id="orderSelect" class="form-select">
                  <option value="">None / general payment</option>
                  <?php foreach ($allOrders as $o): ?>
                    <option value="<?= $o['id'] ?>" data-supplier="<?= $o['supplier_id'] ?>" style="display:none;"><?= e($o['order_number']) ?> (<?= format_money((float) $o['total_amount']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Amount</label>
                  <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Payment Date</label>
                  <input type="date" name="payment_date" class="form-control" required value="<?= e(date('Y-m-d')) ?>">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Payment Method</label>
                  <input type="text" name="payment_method" class="form-control" placeholder="Cash, Bank Transfer, Mobile Money">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Reference</label>
                  <input type="text" name="reference" class="form-control">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100"><i class="bi bi-cash-coin"></i> Record Payment</button>
            </form>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Payment History</h6>
              <form method="get" class="d-flex gap-2">
                <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value="">All Suppliers</option>
                  <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $supplierFilter === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>
            <?php if (!$payments): ?>
              <?php render_empty_state('No payments recorded yet.'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Date</th><th>Supplier</th><th>Order</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
                  <tbody>
                  <?php foreach ($payments as $p): ?>
                    <tr>
                      <td><?= format_date($p['payment_date']) ?></td>
                      <td><?= e($p['supplier_name']) ?></td>
                      <td><?= e($p['order_number'] ?: '-') ?></td>
                      <td><?= format_money((float) $p['amount']) ?></td>
                      <td><?= e($p['payment_method'] ?: '-') ?></td>
                      <td><?= e($p['reference'] ?: '-') ?></td>
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
<script>
document.getElementById('supplierSelect').addEventListener('change', function () {
  var supplierId = this.value;
  var orderSelect = document.getElementById('orderSelect');
  Array.from(orderSelect.options).forEach(function (opt) {
    if (!opt.dataset.supplier) { return; }
    opt.style.display = (opt.dataset.supplier === supplierId) ? '' : 'none';
    if (opt.dataset.supplier !== supplierId && opt.selected) { opt.selected = false; }
  });
  orderSelect.value = '';
});
</script>
<?php render_footer(['context' => 'org-admin']); ?>
