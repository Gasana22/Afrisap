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

$errors = [];
$input = ['harvest_date' => '', 'quantity' => '', 'quality_grade' => '', 'storage_location' => ''];

if (is_post() && csrf_verify()) {
    $input = [
        'harvest_date' => $_POST['harvest_date'] ?? '',
        'quantity' => $_POST['quantity'] ?? '',
        'quality_grade' => clean_string($_POST['quality_grade'] ?? ''),
        'storage_location' => clean_string($_POST['storage_location'] ?? ''),
    ];

    $errors = validate($input, [
        'harvest_date' => 'required|date',
        'quantity' => 'required|numeric',
    ]);

    if (!$errors) {
        $photos = [];
        if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
            $files = $_FILES['photos'];
            foreach ($files['name'] as $i => $name) {
                if ($name === '') {
                    continue;
                }
                $single = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                try {
                    $path = handle_upload($single, $orgId, 'crops');
                    if ($path) {
                        $photos[] = $path;
                    }
                } catch (RuntimeException $e) {
                    $errors['photos'] = $e->getMessage();
                }
            }
        }

        if (!$errors) {
            $id = db_insert('harvest_records', [
                'crop_cycle_id' => $cropCycleId,
                'harvest_date' => $input['harvest_date'],
                'quantity' => clean_float($input['quantity']),
                'quality_grade' => $input['quality_grade'] ?: null,
                'storage_location' => $input['storage_location'] ?: null,
                'photos' => $photos ? json_encode($photos) : null,
                'verified_by' => $currentUser['id'],
            ]);

            $totalYield = (float) db_value('SELECT COALESCE(SUM(quantity), 0) FROM harvest_records WHERE crop_cycle_id = :id', ['id' => $cropCycleId]);
            tenant_update('crop_cycles', $orgId, $cropCycleId, ['actual_yield' => $totalYield]);

            audit_log($orgId, $currentUser['id'], 'create', 'harvest_records', $id, null, $input);
            session_flash('success', 'Harvest recorded and crop cycle actual yield updated.');
            redirect('org-admin/crops/harvest.php?crop_cycle_id=' . $cropCycleId);
        }
    }
}

$GLOBALS['_page_errors'] = $errors;

$harvests = db_all('SELECT * FROM harvest_records WHERE crop_cycle_id = :id ORDER BY harvest_date DESC, created_at DESC', ['id' => $cropCycleId]);
$totalHarvested = (float) db_value('SELECT COALESCE(SUM(quantity), 0) FROM harvest_records WHERE crop_cycle_id = :id', ['id' => $cropCycleId]);

render_header(['title' => 'Harvest - ' . $cycle['crop_batch_id'], 'context' => 'org-admin']);
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
        <li class="breadcrumb-item active">Harvest</li>
      </ol></nav>

      <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-4">
          <?php render_stat_card('Total Harvested', number_format($totalHarvested, 2) . ' units', null, 'bi-basket3-fill', 'info'); ?>
        </div>
        <div class="col-sm-6 col-md-4">
          <?php render_stat_card('Expected Yield', $cycle['expected_yield'] !== null ? number_format((float) $cycle['expected_yield'], 2) . ' units' : '-', null, 'bi-graph-up', 'primary'); ?>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card">
            <h6 class="mb-3">Record Harvest</h6>
            <form method="post" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label small">Harvest Date</label>
                <input type="date" name="harvest_date" class="form-control" required value="<?= e($input['harvest_date']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Quantity</label>
                <input type="number" step="0.01" name="quantity" class="form-control" required value="<?= e((string) $input['quantity']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Quality Grade</label>
                <input type="text" name="quality_grade" class="form-control" value="<?= e($input['quality_grade']) ?>" placeholder="A, B, Premium...">
              </div>
              <div class="mb-2">
                <label class="form-label small">Storage Location</label>
                <input type="text" name="storage_location" class="form-control" value="<?= e($input['storage_location']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small">Photos (optional)</label>
                <input type="file" name="photos[]" class="form-control" accept="image/*" multiple>
              </div>
              <button type="submit" class="btn btn-primary w-100">Save Harvest</button>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="content-card">
            <h6 class="mb-3">Harvest History</h6>
            <?php if (!$harvests): ?>
              <?php render_empty_state('No harvests recorded yet.'); ?>
            <?php else: ?>
              <table class="table-app">
                <thead><tr><th>Date</th><th>Quantity</th><th>Grade</th><th>Storage</th><th>Photos</th></tr></thead>
                <tbody>
                <?php foreach ($harvests as $h): ?>
                  <tr>
                    <td><?= format_date($h['harvest_date']) ?></td>
                    <td><?= $h['quantity'] !== null ? number_format((float) $h['quantity'], 2) : '-' ?></td>
                    <td><?= e($h['quality_grade'] ?: '-') ?></td>
                    <td><?= e($h['storage_location'] ?: '-') ?></td>
                    <td>
                      <?php $hp = $h['photos'] ? json_decode($h['photos'], true) : []; ?>
                      <?php if ($hp): ?>
                        <?php foreach ($hp as $photo): ?><a href="<?= e(uploaded_file_url($photo)) ?>" target="_blank"><i class="bi bi-image"></i></a> <?php endforeach; ?>
                      <?php else: ?>-<?php endif; ?>
                    </td>
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
