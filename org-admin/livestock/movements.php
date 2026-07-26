<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$animalId = (int) clean_int($_GET['animal_id'] ?? $_POST['animal_id'] ?? 0);
$animal = tenant_find('animals', $orgId, $animalId);

if (!$animal) {
    session_flash('error', 'Animal not found.');
    redirect('org-admin/livestock/index.php');
}

$errors = [];
$input = ['from_farm_id' => (string) $animal['farm_id'], 'to_farm_id' => '', 'movement_date' => date('Y-m-d'), 'reason' => '', 'notes' => ''];

if (is_post() && csrf_verify()) {
    $input = [
        'from_farm_id' => $_POST['from_farm_id'] ?? '',
        'to_farm_id' => $_POST['to_farm_id'] ?? '',
        'movement_date' => $_POST['movement_date'] ?? '',
        'reason' => clean_string($_POST['reason'] ?? ''),
        'notes' => clean_string($_POST['notes'] ?? ''),
    ];

    $errors = validate($input, [
        'to_farm_id' => 'required',
        'movement_date' => 'required|date',
    ]);

    $fromFarmId = clean_int($input['from_farm_id']);
    $toFarmId = clean_int($input['to_farm_id']);

    if ($fromFarmId && !tenant_find('farms', $orgId, $fromFarmId)) {
        $errors['from_farm_id'] = 'Invalid origin farm.';
    }
    if ($toFarmId && !tenant_find('farms', $orgId, $toFarmId)) {
        $errors['to_farm_id'] = 'Invalid destination farm.';
    }

    if (!$errors) {
        db_insert('animal_movements', [
            'animal_id' => $animalId,
            'from_farm_id' => $fromFarmId,
            'to_farm_id' => $toFarmId,
            'movement_date' => $input['movement_date'],
            'reason' => $input['reason'] ?: null,
            'notes' => $input['notes'] ?: null,
            'created_by' => $currentUser['id'],
        ]);

        if ($toFarmId && $toFarmId !== (int) $animal['farm_id']) {
            tenant_update('animals', $orgId, $animalId, ['farm_id' => $toFarmId]);
            audit_log($orgId, $currentUser['id'], 'update', 'animals', $animalId, $animal, ['farm_id' => $toFarmId]);
        }

        session_flash('success', 'Movement logged.');
        redirect('org-admin/livestock/movements.php?animal_id=' . $animalId);
    }
}

$GLOBALS['_page_errors'] = $errors;

$farms = tenant_all('farms', $orgId, 'ORDER BY name');
$movements = db_all(
    'SELECT m.*, ff.name AS from_farm_name, tf.name AS to_farm_name FROM animal_movements m LEFT JOIN farms ff ON ff.id = m.from_farm_id LEFT JOIN farms tf ON tf.id = m.to_farm_id WHERE m.animal_id = :animal_id ORDER BY m.movement_date DESC, m.id DESC',
    ['animal_id' => $animalId]
);

render_header(['title' => 'Log Movement - ' . ($animal['name'] ?: $animal['animal_id']), 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/livestock/index.php') ?>">Livestock</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/livestock/view.php?id=' . $animal['id']) ?>"><?= e($animal['name'] ?: $animal['animal_id']) ?></a></li>
        <li class="breadcrumb-item active">Log Movement</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-5">
          <div class="content-card">
            <h6 class="mb-3">Log Movement</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label">From Farm</label>
                <select name="from_farm_id" class="form-select">
                  <option value="">Unknown / external</option>
                  <?php foreach ($farms as $farm): ?>
                    <option value="<?= $farm['id'] ?>" <?= (string) $input['from_farm_id'] === (string) $farm['id'] ? 'selected' : '' ?>><?= e($farm['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">To Farm</label>
                <select name="to_farm_id" class="form-select" required>
                  <option value="">Select farm</option>
                  <?php foreach ($farms as $farm): ?>
                    <option value="<?= $farm['id'] ?>" <?= (string) $input['to_farm_id'] === (string) $farm['id'] ? 'selected' : '' ?>><?= e($farm['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">Movement Date</label>
                <input type="date" name="movement_date" class="form-control" required value="<?= e($input['movement_date']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" value="<?= e($input['reason']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($input['notes']) ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100">Save Movement</button>
            </form>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card">
            <h6 class="mb-3">Movement History</h6>
            <?php if (!$movements): ?>
              <?php render_empty_state('No movements logged yet.', 'bi-truck'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Date</th><th>From</th><th>To</th><th>Reason</th></tr></thead>
                  <tbody>
                    <?php foreach ($movements as $mv): ?>
                      <tr>
                        <td><?= e(format_date($mv['movement_date'])) ?></td>
                        <td><?= e($mv['from_farm_name'] ?: '-') ?></td>
                        <td><?= e($mv['to_farm_name'] ?: '-') ?></td>
                        <td><?= e($mv['reason'] ?: '-') ?></td>
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
<?php render_footer(['context' => 'org-admin']); ?>
