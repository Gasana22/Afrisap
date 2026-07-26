<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$id = (int) clean_int($_GET['id'] ?? 0);
$cycle = tenant_find('crop_cycles', $orgId, $id);

if (!$cycle) {
    session_flash('error', 'Crop cycle not found.');
    redirect('org-admin/crops/index.php');
}

if (is_post() && csrf_verify() && isset($_POST['status'])) {
    $newStatus = $_POST['status'];
    if (in_array($newStatus, CROP_CYCLE_STATUSES, true)) {
        tenant_update('crop_cycles', $orgId, $id, ['status' => $newStatus]);
        audit_log($orgId, $currentUser['id'], 'update', 'crop_cycles', $id, ['status' => $cycle['status']], ['status' => $newStatus]);
        session_flash('success', 'Crop cycle status updated to ' . humanize($newStatus) . '.');
    } else {
        session_flash('error', 'Invalid status.');
    }
    redirect('org-admin/crops/view.php?id=' . $id);
}

$farm = db_one('SELECT name FROM farms WHERE id = :id', ['id' => $cycle['farm_id']]);
$plot = db_one(
    'SELECT p.name AS plot_name, b.name AS block_name FROM plots p JOIN blocks b ON b.id = p.block_id WHERE p.id = :id',
    ['id' => $cycle['plot_id']]
);

$operations = db_all('SELECT * FROM crop_operations WHERE crop_cycle_id = :id', ['id' => $id]);
$procurements = db_all('SELECT * FROM crop_procurements WHERE crop_cycle_id = :id', ['id' => $id]);
$harvests = db_all('SELECT * FROM harvest_records WHERE crop_cycle_id = :id', ['id' => $id]);
$sales = db_all('SELECT * FROM crop_sales WHERE crop_cycle_id = :id', ['id' => $id]);

$timeline = [];
foreach ($operations as $r) {
    $timeline[] = [
        'date' => $r['activity_date'], 'type' => 'operation', 'icon' => 'bi-clipboard-check', 'color' => 'primary',
        'title' => humanize($r['activity_type']),
        'detail' => trim(($r['description'] ?: '') . ($r['cost'] ? ' - ' . format_money((float) $r['cost']) : '')),
    ];
}
foreach ($procurements as $r) {
    $timeline[] = [
        'date' => $r['purchase_date'], 'type' => 'procurement', 'icon' => 'bi-basket', 'color' => 'secondary',
        'title' => 'Procured ' . $r['item_name'],
        'detail' => trim(($r['quantity'] !== null ? number_format((float) $r['quantity'], 2) . ' ' . ($r['unit'] ?: '') : '') . ($r['cost'] ? ' - ' . format_money((float) $r['cost']) : '')),
    ];
}
foreach ($harvests as $r) {
    $timeline[] = [
        'date' => $r['harvest_date'], 'type' => 'harvest', 'icon' => 'bi-basket3-fill', 'color' => 'info',
        'title' => 'Harvest recorded',
        'detail' => ($r['quantity'] !== null ? number_format((float) $r['quantity'], 2) . ' units' : '') . ($r['quality_grade'] ? ' - Grade ' . $r['quality_grade'] : ''),
    ];
}
foreach ($sales as $r) {
    $timeline[] = [
        'date' => $r['sale_date'], 'type' => 'sale', 'icon' => 'bi-cash-coin', 'color' => 'danger',
        'title' => 'Sold to ' . ($r['buyer_name'] ?: 'buyer'),
        'detail' => format_money((float) $r['total_amount']) . ' (' . humanize($r['payment_status']) . ')',
    ];
}

usort($timeline, fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));

render_header(['title' => 'Crop Cycle - ' . $cycle['crop_batch_id'], 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/crops/index.php') ?>">Crop Management</a></li>
        <li class="breadcrumb-item active"><?= e($cycle['crop_batch_id']) ?></li>
      </ol></nav>

      <div class="row g-3 mb-3">
        <div class="col-lg-8">
          <div class="content-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h5 class="mb-0"><?= e($cycle['crop_type']) ?><?= $cycle['variety'] ? ' <span class="text-muted">(' . e($cycle['variety']) . ')</span>' : '' ?></h5>
                <p class="text-muted small mb-0"><code><?= e($cycle['crop_batch_id']) ?></code></p>
              </div>
              <?php render_status_badge($cycle['status']); ?>
            </div>
            <div class="row mt-3">
              <div class="col-sm-6 col-md-3 mb-2">
                <div class="small text-muted">Farm / Plot</div>
                <div class="fw-semibold"><?= e($farm['name'] ?? '-') ?></div>
                <div class="small text-muted"><?= e($plot['block_name'] ?? '') ?> / <?= e($plot['plot_name'] ?? '') ?></div>
              </div>
              <div class="col-sm-6 col-md-3 mb-2">
                <div class="small text-muted">Season</div>
                <div class="fw-semibold"><?= e($cycle['season'] ?: '-') ?></div>
              </div>
              <div class="col-sm-6 col-md-3 mb-2">
                <div class="small text-muted">Start Date</div>
                <div class="fw-semibold"><?= format_date($cycle['start_date']) ?></div>
              </div>
              <div class="col-sm-6 col-md-3 mb-2">
                <div class="small text-muted">Budget</div>
                <div class="fw-semibold"><?= $cycle['budget'] !== null ? format_money((float) $cycle['budget']) : '-' ?></div>
              </div>
              <div class="col-sm-6 col-md-3 mb-2">
                <div class="small text-muted">Expected Yield</div>
                <div class="fw-semibold"><?= $cycle['expected_yield'] !== null ? number_format((float) $cycle['expected_yield'], 2) : '-' ?></div>
              </div>
              <div class="col-sm-6 col-md-3 mb-2">
                <div class="small text-muted">Actual Yield</div>
                <div class="fw-semibold"><?= $cycle['actual_yield'] !== null ? number_format((float) $cycle['actual_yield'], 2) : '-' ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="content-card h-100">
            <h6 class="mb-3">Change Status</h6>
            <form method="post">
              <?= csrf_field() ?>
              <select name="status" class="form-select mb-2">
                <?php foreach (CROP_CYCLE_STATUSES as $s): ?>
                  <option value="<?= e($s) ?>" <?= $cycle['status'] === $s ? 'selected' : '' ?>><?= e(humanize($s)) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-primary w-100">Update Status</button>
            </form>
            <hr>
            <div class="d-grid gap-2">
              <a href="<?= base_url('org-admin/crops/operations.php?crop_cycle_id=' . $id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-clipboard-check"></i> Field Operations</a>
              <a href="<?= base_url('org-admin/crops/procurement.php?crop_cycle_id=' . $id) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-basket"></i> Procurement</a>
              <a href="<?= base_url('org-admin/crops/harvest.php?crop_cycle_id=' . $id) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-basket3-fill"></i> Harvest</a>
              <a href="<?= base_url('org-admin/crops/sales.php?crop_cycle_id=' . $id) ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-cash-coin"></i> Sales</a>
            </div>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Crop Cycle Timeline</h6></div>
        <?php if (!$timeline): ?>
          <?php render_empty_state('No activity logged yet. Use the quick links above to log operations, procurement, harvest, or sales.', 'bi-hourglass'); ?>
        <?php else: ?>
          <ul class="timeline list-unstyled">
            <?php foreach ($timeline as $entry): ?>
              <li class="d-flex gap-3 py-3 border-bottom">
                <div class="text-<?= e($entry['color']) ?>"><i class="bi <?= e($entry['icon']) ?> fs-5"></i></div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between">
                    <strong><?= e($entry['title']) ?></strong>
                    <span class="small text-muted"><?= format_date($entry['date']) ?></span>
                  </div>
                  <?php if ($entry['detail']): ?><div class="small text-muted"><?= e($entry['detail']) ?></div><?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
