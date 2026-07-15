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

$PAYMENT_STATUSES = ['pending', 'paid', 'partial'];

$errors = [];
$input = [
    'sale_date' => '', 'buyer_name' => '', 'quantity' => '', 'unit' => '',
    'unit_price' => '', 'payment_status' => 'pending', 'notes' => '',
];

if (is_post() && csrf_verify()) {
    $input = [
        'sale_date' => $_POST['sale_date'] ?? '',
        'buyer_name' => clean_string($_POST['buyer_name'] ?? ''),
        'quantity' => $_POST['quantity'] ?? '',
        'unit' => clean_string($_POST['unit'] ?? ''),
        'unit_price' => $_POST['unit_price'] ?? '',
        'payment_status' => $_POST['payment_status'] ?? 'pending',
        'notes' => clean_string($_POST['notes'] ?? ''),
    ];

    $errors = validate($input, [
        'sale_date' => 'required|date',
        'quantity' => 'required|numeric',
        'unit_price' => 'numeric',
        'payment_status' => 'in:' . implode(',', $PAYMENT_STATUSES),
    ]);

    if (!$errors) {
        $quantity = clean_float($input['quantity']);
        $unitPrice = $input['unit_price'] !== '' ? clean_float($input['unit_price']) : 0.0;
        $totalAmount = round($quantity * $unitPrice, 2);

        $id = db_insert('crop_sales', [
            'crop_cycle_id' => $cropCycleId,
            'sale_date' => $input['sale_date'],
            'buyer_name' => $input['buyer_name'] ?: null,
            'quantity' => $quantity,
            'unit' => $input['unit'] ?: null,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'payment_status' => $input['payment_status'] ?: 'pending',
            'notes' => $input['notes'] ?: null,
            'created_by' => $currentUser['id'],
        ]);
        audit_log($orgId, $currentUser['id'], 'create', 'crop_sales', $id, null, $input);
        session_flash('success', 'Sale recorded.');
        redirect('org-admin/crops/sales.php?crop_cycle_id=' . $cropCycleId);
    }
}

$GLOBALS['_page_errors'] = $errors;

$sales = db_all('SELECT * FROM crop_sales WHERE crop_cycle_id = :id ORDER BY sale_date DESC, created_at DESC', ['id' => $cropCycleId]);
$totalSales = (float) db_value('SELECT COALESCE(SUM(total_amount), 0) FROM crop_sales WHERE crop_cycle_id = :id', ['id' => $cropCycleId]);

render_header(['title' => 'Sales - ' . $cycle['crop_batch_id'], 'context' => 'org-admin']);
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
        <li class="breadcrumb-item active">Sales</li>
      </ol></nav>

      <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-4">
          <?php render_stat_card('Total Sales Revenue', format_money($totalSales), null, 'bi-cash-coin', 'secondary'); ?>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card">
            <h6 class="mb-3">Record Sale</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label small">Sale Date</label>
                <input type="date" name="sale_date" class="form-control" required value="<?= e($input['sale_date']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Buyer Name</label>
                <input type="text" name="buyer_name" class="form-control" value="<?= e($input['buyer_name']) ?>">
              </div>
              <div class="row">
                <div class="col-6 mb-2">
                  <label class="form-label small">Quantity</label>
                  <input type="number" step="0.01" name="quantity" class="form-control" required value="<?= e((string) $input['quantity']) ?>">
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label small">Unit</label>
                  <input type="text" name="unit" class="form-control" value="<?= e($input['unit']) ?>" placeholder="kg, bags...">
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small">Unit Price</label>
                <input type="number" step="0.01" name="unit_price" class="form-control" value="<?= e((string) $input['unit_price']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Payment Status</label>
                <select name="payment_status" class="form-select">
                  <?php foreach ($PAYMENT_STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= $input['payment_status'] === $s ? 'selected' : '' ?>><?= e(humanize($s)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small">Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($input['notes']) ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100">Save Sale</button>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="content-card">
            <h6 class="mb-3">Sales History</h6>
            <?php if (!$sales): ?>
              <?php render_empty_state('No sales recorded yet.'); ?>
            <?php else: ?>
              <table class="table-app">
                <thead><tr><th>Date</th><th>Buyer</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Payment</th></tr></thead>
                <tbody>
                <?php foreach ($sales as $s): ?>
                  <tr>
                    <td><?= format_date($s['sale_date']) ?></td>
                    <td><?= e($s['buyer_name'] ?: '-') ?></td>
                    <td><?= number_format((float) $s['quantity'], 2) ?> <?= e($s['unit'] ?: '') ?></td>
                    <td><?= $s['unit_price'] !== null ? format_money((float) $s['unit_price']) : '-' ?></td>
                    <td><strong><?= format_money((float) $s['total_amount']) ?></strong></td>
                    <td><?php render_status_badge($s['payment_status']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-end"><strong>Running Total</strong></td>
                    <td colspan="2"><strong><?= format_money($totalSales) ?></strong></td>
                  </tr>
                </tfoot>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
