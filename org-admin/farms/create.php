<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$limits = organization_within_plan_limits($organization);
if (!$limits['farms']['ok']) {
    session_flash('error', 'You have reached the farm limit for your subscription plan.');
    redirect('org-admin/farms/index.php');
}

$errors = [];
$input = ['name' => '', 'size' => '', 'district' => '', 'village' => '', 'description' => '', 'gps_latitude' => '', 'gps_longitude' => ''];

if (is_post() && csrf_verify()) {
    $input = [
        'name' => clean_string($_POST['name'] ?? ''),
        'size' => $_POST['size'] ?? '',
        'district' => clean_string($_POST['district'] ?? ''),
        'village' => clean_string($_POST['village'] ?? ''),
        'description' => clean_string($_POST['description'] ?? ''),
        'gps_latitude' => $_POST['gps_latitude'] ?? '',
        'gps_longitude' => $_POST['gps_longitude'] ?? '',
    ];

    $errors = validate($input, ['name' => 'required|max:255', 'size' => 'numeric']);

    if (!$errors) {
        $id = tenant_insert('farms', $orgId, [
            'name' => $input['name'],
            'size' => clean_float($input['size']),
            'gps_latitude' => clean_float($input['gps_latitude']),
            'gps_longitude' => clean_float($input['gps_longitude']),
            'district' => $input['district'],
            'village' => $input['village'],
            'description' => $input['description'],
            'created_by' => $currentUser['id'],
        ]);
        audit_log($orgId, $currentUser['id'], 'create', 'farms', $id, null, $input);
        session_flash('success', 'Farm added successfully.');
        redirect('org-admin/farms/index.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Add Farm', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:720px;">
        <h5 class="mb-3">Add New Farm</h5>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Farm Name</label>
            <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Size (acres)</label>
              <input type="number" step="0.01" name="size" class="form-control" value="<?= e((string) $input['size']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">District</label>
              <input type="text" name="district" class="form-control" value="<?= e($input['district']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Village</label>
            <input type="text" name="village" class="form-control" value="<?= e($input['village']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">GPS Location <span class="text-muted small">(click the map or drag the marker)</span></label>
            <div id="farmMap" style="height:300px;border-radius:12px;"></div>
            <div class="row mt-2">
              <div class="col-6"><input type="text" id="gps_latitude" name="gps_latitude" class="form-control form-control-sm" placeholder="Latitude" value="<?= e((string) $input['gps_latitude']) ?>"></div>
              <div class="col-6"><input type="text" id="gps_longitude" name="gps_longitude" class="form-control form-control-sm" placeholder="Longitude" value="<?= e((string) $input['gps_longitude']) ?>"></div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($input['description']) ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Farm</button>
            <a href="<?= base_url('org-admin/farms/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => initMapPicker('farmMap', 'gps_latitude', 'gps_longitude'));
</script>
<?php render_footer(['context' => 'org-admin', 'js' => ['map']]); ?>
