<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

if (is_post() && ($_POST['_method'] ?? '') === 'DELETE' && csrf_verify()) {
    $id = clean_int($_POST['id'] ?? 0);
    $animal = tenant_find('animals', $orgId, $id);
    if ($animal) {
        tenant_delete('animals', $orgId, $id);
        audit_log($orgId, $currentUser['id'], 'delete', 'animals', $id, $animal, null);
        session_flash('success', 'Animal record deleted.');
    }
    redirect('org-admin/livestock/index.php');
}

$search = trim($_GET['search'] ?? '');
$speciesFilter = trim($_GET['species'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$conditions = ['a.organization_id = :organization_id'];
$params = ['organization_id' => $orgId];

if ($search !== '') {
    $conditions[] = '(a.name LIKE :search_name OR a.animal_id LIKE :search_animal_id OR a.tag_number LIKE :search_tag)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_animal_id'] = '%' . $search . '%';
    $params['search_tag'] = '%' . $search . '%';
}
if ($speciesFilter !== '') {
    $conditions[] = 'a.species = :species';
    $params['species'] = $speciesFilter;
}
if ($statusFilter !== '') {
    $conditions[] = 'a.status = :status';
    $params['status'] = $statusFilter;
}

$where = implode(' AND ', $conditions);
$animals = db_all(
    "SELECT a.*, f.name AS farm_name FROM animals a LEFT JOIN farms f ON f.id = a.farm_id WHERE $where ORDER BY a.created_at DESC",
    $params
);

$speciesList = db_all(
    'SELECT DISTINCT species FROM animals WHERE organization_id = :organization_id ORDER BY species',
    ['organization_id' => $orgId]
);

render_header(['title' => 'Livestock', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Livestock</h4>
          <p class="text-muted small mb-0">Manage animal records, health events and movements</p>
        </div>
        <a href="<?= base_url('org-admin/livestock/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Animal</a>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name, animal ID or tag number" value="<?= e($search) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Species</label>
            <select name="species" class="form-select">
              <option value="">All species</option>
              <?php foreach ($speciesList as $s): ?>
                <option value="<?= e($s['species']) ?>" <?= $speciesFilter === $s['species'] ? 'selected' : '' ?>><?= e(humanize($s['species'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
              <option value="">All statuses</option>
              <?php foreach (['active', 'sold', 'deceased', 'transferred'] as $st): ?>
                <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e(humanize($st)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$animals): ?>
          <?php render_empty_state('No animals found. Add your first animal to get started.', 'bi-egg-fried'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Animal ID</th>
                  <th>Tag #</th>
                  <th>Name</th>
                  <th>Species / Breed</th>
                  <th>Gender</th>
                  <th>Age</th>
                  <th>Farm</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($animals as $animal): ?>
                  <tr>
                    <td><?= e($animal['animal_id']) ?></td>
                    <td><?= e($animal['tag_number'] ?: '-') ?></td>
                    <td><?= e($animal['name'] ?: '-') ?></td>
                    <td><?= e(humanize($animal['species'])) ?><?= $animal['breed'] ? ' / ' . e($animal['breed']) : '' ?></td>
                    <td><?= e(humanize($animal['gender'])) ?></td>
                    <td><?= e(calculate_age($animal['birth_date'])) ?></td>
                    <td><?= e($animal['farm_name'] ?: '-') ?></td>
                    <td><?php render_status_badge($animal['status']); ?></td>
                    <td class="text-end">
                      <a href="<?= base_url('org-admin/livestock/view.php?id=' . $animal['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAnimal<?= $animal['id'] ?>"><i class="bi bi-trash"></i></button>
                    </td>
                  </tr>
                  <?php confirm_delete_modal('deleteAnimal' . $animal['id'], base_url('org-admin/livestock/index.php'), e($animal['name'] ?: $animal['animal_id']), ['id' => $animal['id']]); ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
