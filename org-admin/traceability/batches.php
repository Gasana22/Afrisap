<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$BATCH_STATUSES = ['active', 'completed', 'archived'];

$errors = [];
$input = [
    'product_type' => '',
    'crop_cycle_id' => '',
    'farm_id' => '',
    'block_id' => '',
    'plot_id' => '',
    'production_date' => '',
    'quantity' => '',
    'unit' => '',
    'current_location' => '',
];

if (is_post() && csrf_verify()) {
    $input = [
        'product_type' => clean_string($_POST['product_type'] ?? ''),
        'crop_cycle_id' => $_POST['crop_cycle_id'] ?? '',
        'farm_id' => $_POST['farm_id'] ?? '',
        'block_id' => $_POST['block_id'] ?? '',
        'plot_id' => $_POST['plot_id'] ?? '',
        'production_date' => $_POST['production_date'] ?? '',
        'quantity' => $_POST['quantity'] ?? '',
        'unit' => clean_string($_POST['unit'] ?? ''),
        'current_location' => clean_string($_POST['current_location'] ?? ''),
    ];

    $errors = validate($input, [
        'product_type' => 'required|max:100',
        'quantity' => 'numeric',
        'production_date' => 'date',
    ]);

    if (!$errors) {
        $batchId = generate_batch_id('TRC');

        $newId = tenant_insert('trace_batches', $orgId, [
            'batch_id' => $batchId,
            'product_type' => $input['product_type'],
            'crop_cycle_id' => clean_int($input['crop_cycle_id']),
            'farm_id' => clean_int($input['farm_id']),
            'block_id' => clean_int($input['block_id']),
            'plot_id' => clean_int($input['plot_id']),
            'production_date' => $input['production_date'] !== '' ? $input['production_date'] : null,
            'quantity' => clean_float($input['quantity']),
            'unit' => $input['unit'] !== '' ? $input['unit'] : null,
            'current_location' => $input['current_location'] !== '' ? $input['current_location'] : null,
        ]);

        audit_log($orgId, $currentUser['id'], 'create', 'trace_batches', $newId, null, array_merge($input, ['batch_id' => $batchId]));
        session_flash('success', 'Batch ' . $batchId . ' created. Generate its QR code below.');
        redirect('org-admin/traceability/view-batch.php?id=' . $newId);
    }
}

$GLOBALS['_page_errors'] = $errors;

$status = clean_string($_GET['status'] ?? '');
$search = clean_string($_GET['search'] ?? '');

$conditions = [];
$params = [];

if ($status !== '' && in_array($status, $BATCH_STATUSES, true)) {
    $conditions[] = 'status = :status';
    $params['status'] = $status;
}

if ($search !== '') {
    $conditions[] = '(batch_id LIKE :search OR product_type LIKE :search)';
    $params['search'] = "%$search%";
}

$extraSql = $conditions ? ('AND ' . implode(' AND ', $conditions)) : '';
$extraSql .= ' ORDER BY created_at DESC';

$batches = tenant_all('trace_batches', $orgId, $extraSql, $params);

$cropCycles = tenant_all('crop_cycles', $orgId, 'ORDER BY created_at DESC');
$farms = tenant_all('farms', $orgId, 'ORDER BY name ASC');
$blocks = db_all('SELECT b.* FROM blocks b JOIN farms f ON f.id = b.farm_id WHERE f.organization_id = :org ORDER BY b.name ASC', ['org' => $orgId]);
$plots = db_all('SELECT p.* FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id WHERE f.organization_id = :org ORDER BY p.name ASC', ['org' => $orgId]);

render_header(['title' => 'Traceability Batches', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Traceability Batches</h4>
          <p class="text-muted small mb-0">Track products from farm to consumer with QR-code chain of custody.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('org-admin/traceability/qr-codes.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-qr-code"></i> QR Codes</a>
          <a href="<?= base_url('org-admin/traceability/audit.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Audit Trail</a>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newBatchModal"><i class="bi bi-plus-lg"></i> New Batch</button>
        </div>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Batch ID or product type" value="<?= e($search) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
              <option value="">All Statuses</option>
              <?php foreach ($BATCH_STATUSES as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(humanize($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/traceability/batches.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$batches): ?>
          <?php render_empty_state('No trace batches yet. Create your first batch to start tracking its chain of custody.', 'bi-box-seam'); ?>
        <?php else: ?>
          <table class="table-app">
            <thead>
              <tr>
                <th>Batch ID</th>
                <th>Product</th>
                <th>Production Date</th>
                <th>Quantity</th>
                <th>Current Location</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($batches as $b): ?>
                <tr>
                  <td><code><?= e($b['batch_id']) ?></code></td>
                  <td><?= e($b['product_type']) ?></td>
                  <td><?= format_date($b['production_date']) ?></td>
                  <td><?= $b['quantity'] !== null ? number_format((float) $b['quantity'], 2) . ' ' . e($b['unit'] ?: '') : '-' ?></td>
                  <td><?= e($b['current_location'] ?: '-') ?></td>
                  <td><?php render_status_badge($b['status']); ?></td>
                  <td class="text-end">
                    <a href="<?= base_url('org-admin/traceability/view-batch.php?id=' . $b['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<?php modal_open('newBatchModal', 'New Trace Batch'); ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="mb-2">
    <label class="form-label small">Product Type</label>
    <input type="text" name="product_type" class="form-control" required value="<?= e($input['product_type']) ?>" placeholder="e.g. Maize, Tomatoes">
  </div>
  <div class="row">
    <div class="col-md-6 mb-2">
      <label class="form-label small">Crop Cycle (optional)</label>
      <select name="crop_cycle_id" class="form-select">
        <option value="">- None -</option>
        <?php foreach ($cropCycles as $cc): ?>
          <option value="<?= $cc['id'] ?>" <?= (string) $input['crop_cycle_id'] === (string) $cc['id'] ? 'selected' : '' ?>><?= e($cc['crop_batch_id']) ?> (<?= e($cc['crop_type']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label small">Farm (optional)</label>
      <select name="farm_id" class="form-select">
        <option value="">- None -</option>
        <?php foreach ($farms as $f): ?>
          <option value="<?= $f['id'] ?>" <?= (string) $input['farm_id'] === (string) $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-2">
      <label class="form-label small">Block (optional)</label>
      <select name="block_id" class="form-select">
        <option value="">- None -</option>
        <?php foreach ($blocks as $bl): ?>
          <option value="<?= $bl['id'] ?>" <?= (string) $input['block_id'] === (string) $bl['id'] ? 'selected' : '' ?>><?= e($bl['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label small">Plot (optional)</label>
      <select name="plot_id" class="form-select">
        <option value="">- None -</option>
        <?php foreach ($plots as $pl): ?>
          <option value="<?= $pl['id'] ?>" <?= (string) $input['plot_id'] === (string) $pl['id'] ? 'selected' : '' ?>><?= e($pl['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-2">
      <label class="form-label small">Production Date</label>
      <input type="date" name="production_date" class="form-control" value="<?= e($input['production_date']) ?>">
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label small">Current Location</label>
      <input type="text" name="current_location" class="form-control" value="<?= e($input['current_location']) ?>">
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-2">
      <label class="form-label small">Quantity</label>
      <input type="number" step="0.01" name="quantity" class="form-control" value="<?= e((string) $input['quantity']) ?>">
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label small">Unit</label>
      <input type="text" name="unit" class="form-control" placeholder="kg, tons, crates..." value="<?= e($input['unit']) ?>">
    </div>
  </div>
  <div class="modal-footer px-0 pb-0">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary">Create Batch</button>
  </div>
</form>
<?php modal_close(); ?>

<?php if ($errors): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  new bootstrap.Modal(document.getElementById('newBatchModal')).show();
});
</script>
<?php endif; ?>
<?php render_footer(['context' => 'org-admin']); ?>
