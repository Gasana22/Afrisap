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

$ACTIVITY_TYPES = ['planting', 'irrigation', 'spraying', 'weeding', 'fertilizing', 'monitoring', 'harvesting', 'other'];

$errors = [];
$input = ['activity_type' => 'planting', 'activity_date' => '', 'description' => '', 'cost' => '', 'gps_latitude' => '', 'gps_longitude' => ''];

if (is_post() && csrf_verify()) {
    $input = [
        'activity_type' => $_POST['activity_type'] ?? '',
        'activity_date' => $_POST['activity_date'] ?? '',
        'description' => clean_string($_POST['description'] ?? ''),
        'cost' => $_POST['cost'] ?? '',
        'gps_latitude' => $_POST['gps_latitude'] ?? '',
        'gps_longitude' => $_POST['gps_longitude'] ?? '',
    ];

    $errors = validate($input, [
        'activity_type' => 'required|in:' . implode(',', $ACTIVITY_TYPES),
        'activity_date' => 'required|date',
        'cost' => 'numeric',
    ]);

    if (!$errors) {
        $photoUrls = [];
        if (!empty($_FILES['photo']['name'])) {
            try {
                $path = handle_upload($_FILES['photo'], $orgId, 'crops');
                if ($path) {
                    $photoUrls[] = $path;
                }
            } catch (RuntimeException $e) {
                $errors['photo'] = $e->getMessage();
            }
        }

        if (!$errors) {
            $id = db_insert('crop_operations', [
                'crop_cycle_id' => $cropCycleId,
                'activity_type' => $input['activity_type'],
                'activity_date' => $input['activity_date'],
                'description' => $input['description'] ?: null,
                'cost' => $input['cost'] !== '' ? clean_float($input['cost']) : null,
                'gps_latitude' => $input['gps_latitude'] !== '' ? clean_float($input['gps_latitude']) : null,
                'gps_longitude' => $input['gps_longitude'] !== '' ? clean_float($input['gps_longitude']) : null,
                'photo_urls' => $photoUrls ? json_encode($photoUrls) : null,
                'created_by' => $currentUser['id'],
            ]);
            audit_log($orgId, $currentUser['id'], 'create', 'crop_operations', $id, null, $input);
            session_flash('success', 'Field operation logged.');
            redirect('org-admin/crops/operations.php?crop_cycle_id=' . $cropCycleId);
        }
    }
}

$GLOBALS['_page_errors'] = $errors;

$operations = db_all('SELECT * FROM crop_operations WHERE crop_cycle_id = :id ORDER BY activity_date DESC, created_at DESC', ['id' => $cropCycleId]);

render_header(['title' => 'Field Operations - ' . $cycle['crop_batch_id'], 'context' => 'org-admin']);
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
        <li class="breadcrumb-item active">Field Operations</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card">
            <h6 class="mb-3">Log Field Operation</h6>
            <form method="post" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label small">Activity Type</label>
                <select name="activity_type" class="form-select">
                  <?php foreach ($ACTIVITY_TYPES as $type): ?>
                    <option value="<?= e($type) ?>" <?= $input['activity_type'] === $type ? 'selected' : '' ?>><?= e(humanize($type)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label small">Activity Date</label>
                <input type="date" name="activity_date" class="form-control" required value="<?= e($input['activity_date']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($input['description']) ?></textarea>
              </div>
              <div class="mb-2">
                <label class="form-label small">Cost</label>
                <input type="number" step="0.01" name="cost" class="form-control" value="<?= e((string) $input['cost']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">GPS Location</label>
                <div class="d-flex gap-2 mb-1">
                  <input type="hidden" id="gps_latitude" name="gps_latitude" value="<?= e((string) $input['gps_latitude']) ?>">
                  <input type="hidden" id="gps_longitude" name="gps_longitude" value="<?= e((string) $input['gps_longitude']) ?>">
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-gps-capture data-lat-field="gps_latitude" data-lng-field="gps_longitude" data-status-field="gpsStatus"><i class="bi bi-geo-alt"></i> Capture GPS</button>
                </div>
                <div id="gpsStatus" class="small text-muted"></div>
              </div>
              <div class="mb-3">
                <label class="form-label small">Photo (optional)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
              </div>
              <button type="submit" class="btn btn-primary w-100">Log Operation</button>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="content-card">
            <h6 class="mb-3">Operation History</h6>
            <?php if (!$operations): ?>
              <?php render_empty_state('No field operations logged yet.'); ?>
            <?php else: ?>
              <table class="table-app">
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Cost</th><th>GPS</th><th>Photo</th></tr></thead>
                <tbody>
                <?php foreach ($operations as $op): ?>
                  <tr>
                    <td><?= format_date($op['activity_date']) ?></td>
                    <td><?php render_status_badge($op['activity_type']); ?></td>
                    <td><?= e($op['description'] ?: '-') ?></td>
                    <td><?= $op['cost'] !== null ? format_money((float) $op['cost']) : '-' ?></td>
                    <td class="small text-muted"><?= $op['gps_latitude'] !== null ? number_format((float) $op['gps_latitude'], 5) . ', ' . number_format((float) $op['gps_longitude'], 5) : '-' ?></td>
                    <td>
                      <?php $photos = $op['photo_urls'] ? json_decode($op['photo_urls'], true) : []; ?>
                      <?php if ($photos): ?>
                        <a href="<?= e(uploaded_file_url($photos[0])) ?>" target="_blank"><i class="bi bi-image"></i></a>
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
<script src="<?= asset_url('js/gps.js') ?>"></script>
<?php render_footer(['context' => 'org-admin']); ?>
