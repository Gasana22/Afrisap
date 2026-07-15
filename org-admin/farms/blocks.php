<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$farmId = clean_int($_GET['farm_id'] ?? 0);
$farm = tenant_find('farms', $orgId, $farmId);

if (!$farm) {
    session_flash('error', 'Farm not found.');
    redirect('org-admin/farms/index.php');
}

if (is_post() && csrf_verify()) {
    if (($_POST['_method'] ?? '') === 'DELETE') {
        $id = clean_int($_POST['id'] ?? 0);
        db_delete('blocks', 'id = :id AND farm_id = :farm_id', ['id' => $id, 'farm_id' => $farmId]);
        session_flash('success', 'Block deleted.');
    } else {
        $name = clean_string($_POST['name'] ?? '');
        $area = clean_float($_POST['area'] ?? null);
        if ($name === '') {
            session_flash('error', 'Block name is required.');
        } else {
            db_insert('blocks', [
                'farm_id' => $farmId,
                'name' => $name,
                'area' => $area,
                'description' => clean_string($_POST['description'] ?? ''),
            ]);
            session_flash('success', 'Block added.');
        }
    }
    redirect('org-admin/farms/blocks.php?farm_id=' . $farmId);
}

$blocks = db_all('SELECT b.*, (SELECT COUNT(*) FROM plots p WHERE p.block_id = b.id) AS plot_count FROM blocks b WHERE b.farm_id = :farm_id ORDER BY b.created_at DESC', ['farm_id' => $farmId]);

render_header(['title' => 'Blocks - ' . $farm['name'], 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/farms/index.php') ?>">Farms</a></li>
        <li class="breadcrumb-item active"><?= e($farm['name']) ?> Blocks</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card">
            <h6 class="mb-3">Add Block</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Block name" required></div>
              <div class="mb-2"><input type="number" step="0.01" name="area" class="form-control" placeholder="Area (acres)"></div>
              <div class="mb-2"><textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea></div>
              <button type="submit" class="btn btn-primary w-100">Add Block</button>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="content-card">
            <h6 class="mb-3">Blocks in <?= e($farm['name']) ?></h6>
            <?php if (!$blocks): ?>
              <?php render_empty_state('No blocks yet. Add one on the left.'); ?>
            <?php else: ?>
              <table class="table-app">
                <thead><tr><th>Name</th><th>Area</th><th>Plots</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($blocks as $b): ?>
                  <tr>
                    <td><?= e($b['name']) ?></td>
                    <td><?= $b['area'] ? number_format($b['area'], 1) . ' acres' : '-' ?></td>
                    <td><?= (int) $b['plot_count'] ?></td>
                    <td class="text-end">
                      <a href="<?= base_url('org-admin/farms/plots.php?block_id=' . $b['id']) ?>" class="btn btn-sm btn-outline-primary">Plots</a>
                      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delBlock<?= $b['id'] ?>"><i class="bi bi-trash"></i></button>
                    </td>
                  </tr>
                  <?php confirm_delete_modal('delBlock' . $b['id'], base_url('org-admin/farms/blocks.php?farm_id=' . $farmId), e($b['name']), ['id' => $b['id']]); ?>
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
