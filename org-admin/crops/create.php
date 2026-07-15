<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];
$input = [
    'crop_type' => '', 'variety' => '', 'season' => '', 'farm_id' => '', 'plot_id' => '',
    'start_date' => '', 'budget' => '', 'expected_yield' => '',
];

if (is_post() && csrf_verify()) {
    $input = [
        'crop_type' => clean_string($_POST['crop_type'] ?? ''),
        'variety' => clean_string($_POST['variety'] ?? ''),
        'season' => clean_string($_POST['season'] ?? ''),
        'farm_id' => $_POST['farm_id'] ?? '',
        'plot_id' => $_POST['plot_id'] ?? '',
        'start_date' => $_POST['start_date'] ?? '',
        'budget' => $_POST['budget'] ?? '',
        'expected_yield' => $_POST['expected_yield'] ?? '',
    ];

    $errors = validate($input, [
        'crop_type' => 'required|max:100',
        'farm_id' => 'required',
        'plot_id' => 'required',
        'start_date' => 'date',
        'budget' => 'numeric',
        'expected_yield' => 'numeric',
    ]);

    $farmId = clean_int($input['farm_id']);
    $plotId = clean_int($input['plot_id']);

    $farm = $farmId ? tenant_find('farms', $orgId, $farmId) : null;
    if (!$farm) {
        $errors['farm_id'] = 'Please choose a valid farm.';
    }

    $plot = $plotId ? db_one(
        'SELECT p.* FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
         WHERE p.id = :id AND f.organization_id = :org_id',
        ['id' => $plotId, 'org_id' => $orgId]
    ) : null;
    if (!$plot) {
        $errors['plot_id'] = 'Please choose a valid plot.';
    }

    if (!$errors) {
        $batchId = generate_batch_id('CROP');
        $id = tenant_insert('crop_cycles', $orgId, [
            'farm_id' => $farmId,
            'plot_id' => $plotId,
            'crop_type' => $input['crop_type'],
            'variety' => $input['variety'] ?: null,
            'season' => $input['season'] ?: null,
            'crop_batch_id' => $batchId,
            'start_date' => $input['start_date'] ?: null,
            'status' => 'planning',
            'budget' => $input['budget'] !== '' ? clean_float($input['budget']) : null,
            'expected_yield' => $input['expected_yield'] !== '' ? clean_float($input['expected_yield']) : null,
            'created_by' => $currentUser['id'],
        ]);
        audit_log($orgId, $currentUser['id'], 'create', 'crop_cycles', $id, null, $input);
        session_flash('success', "Crop cycle $batchId started successfully.");
        redirect('org-admin/crops/view.php?id=' . $id);
    }
}

$GLOBALS['_page_errors'] = $errors;

$farms = tenant_all('farms', $orgId, 'ORDER BY name');
$plots = db_all(
    'SELECT p.id, p.name AS plot_name, b.name AS block_name, f.name AS farm_name, f.id AS farm_id
     FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
     WHERE f.organization_id = :org_id ORDER BY f.name, b.name, p.name',
    ['org_id' => $orgId]
);

render_header(['title' => 'Start New Crop Cycle', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:780px;">
        <h5 class="mb-3">Start New Crop Cycle</h5>
        <?php if (!$farms): ?>
          <?php render_empty_state('You need at least one farm before starting a crop cycle.', 'bi-geo-alt'); ?>
          <a href="<?= base_url('org-admin/farms/create.php') ?>" class="btn btn-primary">Add a Farm</a>
        <?php elseif (!$plots): ?>
          <?php render_empty_state('You need at least one plot (Farm &rarr; Block &rarr; Plot) before starting a crop cycle.', 'bi-grid'); ?>
          <a href="<?= base_url('org-admin/farms/index.php') ?>" class="btn btn-primary">Manage Farms</a>
        <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Crop Type</label>
              <input type="text" name="crop_type" class="form-control" required value="<?= e($input['crop_type']) ?>" placeholder="e.g. Maize, Tomato">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Variety</label>
              <input type="text" name="variety" class="form-control" value="<?= e($input['variety']) ?>" placeholder="e.g. Hybrid 614">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Season</label>
              <input type="text" name="season" class="form-control" value="<?= e($input['season']) ?>" placeholder="e.g. 2026 Long Rains">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control" value="<?= e($input['start_date']) ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Farm</label>
              <select name="farm_id" class="form-select" required>
                <option value="">Select farm</option>
                <?php foreach ($farms as $farm): ?>
                  <option value="<?= $farm['id'] ?>" <?= (string) $input['farm_id'] === (string) $farm['id'] ? 'selected' : '' ?>><?= e($farm['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Plot</label>
              <select name="plot_id" class="form-select" required>
                <option value="">Select plot</option>
                <?php foreach ($plots as $p): ?>
                  <option value="<?= $p['id'] ?>" <?= (string) $input['plot_id'] === (string) $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['farm_name']) ?> - <?= e($p['block_name']) ?> - <?= e($p['plot_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Budget</label>
              <input type="number" step="0.01" name="budget" class="form-control" value="<?= e((string) $input['budget']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Expected Yield</label>
              <input type="number" step="0.01" name="expected_yield" class="form-control" value="<?= e((string) $input['expected_yield']) ?>">
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Start Crop Cycle</button>
            <a href="<?= base_url('org-admin/crops/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
