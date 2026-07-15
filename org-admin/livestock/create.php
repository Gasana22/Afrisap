<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];
$input = [
    'farm_id' => '',
    'tag_number' => '',
    'name' => '',
    'species' => '',
    'breed' => '',
    'gender' => '',
    'birth_date' => '',
    'purchase_price' => '',
];
$selectedParentIds = [];

if (is_post() && csrf_verify()) {
    $input = [
        'farm_id' => $_POST['farm_id'] ?? '',
        'tag_number' => clean_string($_POST['tag_number'] ?? ''),
        'name' => clean_string($_POST['name'] ?? ''),
        'species' => clean_string($_POST['species'] ?? ''),
        'breed' => clean_string($_POST['breed'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'birth_date' => $_POST['birth_date'] ?? '',
        'purchase_price' => $_POST['purchase_price'] ?? '',
    ];
    $selectedParentIds = array_values(array_filter(array_map('clean_string', (array) ($_POST['parent_ids'] ?? []))));

    $errors = validate($input, [
        'farm_id' => 'required',
        'species' => 'required|max:100',
        'gender' => 'required|in:male,female',
        'birth_date' => 'date',
        'purchase_price' => 'numeric',
    ]);

    $farmId = clean_int($input['farm_id']);
    $farm = $farmId ? tenant_find('farms', $orgId, $farmId) : null;
    if (!$farm) {
        $errors['farm_id'] = 'Please select a valid farm.';
    }

    if (!$errors) {
        $animalId = generate_animal_id();
        while (db_value('SELECT COUNT(*) FROM animals WHERE animal_id = :animal_id', ['animal_id' => $animalId]) > 0) {
            $animalId = generate_animal_id();
        }

        $newId = tenant_insert('animals', $orgId, [
            'farm_id' => $farmId,
            'animal_id' => $animalId,
            'tag_number' => $input['tag_number'] ?: null,
            'name' => $input['name'] ?: null,
            'species' => $input['species'],
            'breed' => $input['breed'] ?: null,
            'gender' => $input['gender'],
            'birth_date' => $input['birth_date'] ?: null,
            'parent_ids' => json_encode($selectedParentIds),
            'purchase_price' => clean_float($input['purchase_price']),
            'created_by' => $currentUser['id'],
        ]);

        audit_log($orgId, $currentUser['id'], 'create', 'animals', $newId, null, array_merge($input, ['animal_id' => $animalId, 'parent_ids' => $selectedParentIds]));
        session_flash('success', 'Animal added successfully.');
        redirect('org-admin/livestock/view.php?id=' . $newId);
    }
}

$GLOBALS['_page_errors'] = $errors;

$farms = tenant_all('farms', $orgId, "AND status = 'active' ORDER BY name");
$existingAnimals = tenant_all('animals', $orgId, 'ORDER BY name, animal_id');

render_header(['title' => 'Add Animal', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:760px;">
        <h5 class="mb-3">Add New Animal</h5>
        <form method="post">
          <?= csrf_field() ?>
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
              <label class="form-label">Tag Number</label>
              <input type="text" name="tag_number" class="form-control" value="<?= e($input['tag_number']) ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" value="<?= e($input['name']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select" required>
                <option value="">Select gender</option>
                <option value="male" <?= $input['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $input['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Species</label>
              <input type="text" name="species" class="form-control" list="speciesOptions" required value="<?= e($input['species']) ?>">
              <datalist id="speciesOptions">
                <option value="cattle"><option value="goat"><option value="sheep"><option value="pig"><option value="poultry"><option value="rabbit">
              </datalist>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Breed</label>
              <input type="text" name="breed" class="form-control" value="<?= e($input['breed']) ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Birth Date</label>
              <input type="date" name="birth_date" class="form-control" value="<?= e($input['birth_date']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Purchase Price</label>
              <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= e((string) $input['purchase_price']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Parents <span class="text-muted small">(optional, hold Ctrl/Cmd to select multiple)</span></label>
            <select name="parent_ids[]" class="form-select" multiple size="5">
              <?php foreach ($existingAnimals as $ea): ?>
                <option value="<?= e($ea['animal_id']) ?>" <?= in_array($ea['animal_id'], $selectedParentIds, true) ? 'selected' : '' ?>>
                  <?= e($ea['animal_id']) ?><?= $ea['name'] ? ' - ' . e($ea['name']) : '' ?> (<?= e(humanize($ea['species'])) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Animal</button>
            <a href="<?= base_url('org-admin/livestock/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
