<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$blockId = (int) clean_int($_GET['block_id'] ?? 0);
$block = db_one(
    'SELECT b.*, f.name AS farm_name, f.id AS farm_id FROM blocks b
     JOIN farms f ON f.id = b.farm_id WHERE b.id = :id AND f.organization_id = :org_id',
    ['id' => $blockId, 'org_id' => $orgId]
);

if (!$block) {
    session_flash('error', 'Block not found.');
    redirect('org-admin/farms/index.php');
}

if (is_post() && csrf_verify()) {
    if (($_POST['_method'] ?? '') === 'DELETE') {
        $id = clean_int($_POST['id'] ?? 0);
        db_delete('plots', 'id = :id AND block_id = :block_id', ['id' => $id, 'block_id' => $blockId]);
        session_flash('success', 'Plot deleted.');
    } else {
        $name = clean_string($_POST['name'] ?? '');
        if ($name === '') {
            session_flash('error', 'Plot name is required.');
        } else {
            db_insert('plots', [
                'block_id' => $blockId,
                'name' => $name,
                'size' => clean_float($_POST['size'] ?? null),
                'soil_type' => clean_string($_POST['soil_type'] ?? ''),
                'crop_assignment' => clean_string($_POST['crop_assignment'] ?? ''),
            ]);
            session_flash('success', 'Plot added.');
        }
    }
    redirect('org-admin/farms/plots.php?block_id=' . $blockId);
}

$plots = db_all('SELECT * FROM plots WHERE block_id = :block_id ORDER BY created_at DESC', ['block_id' => $blockId]);

render_header(['title' => 'Plots - ' . $block['name'], 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/farms/index.php') ?>">Farms</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/farms/blocks.php?farm_id=' . $block['farm_id']) ?>"><?= e($block['farm_name']) ?></a></li>
        <li class="breadcrumb-item active"><?= e($block['name']) ?> Plots</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card">
            <h6 class="mb-3">Add Plot</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Plot name" required></div>
              <div class="mb-2"><input type="number" step="0.01" name="size" class="form-control" placeholder="Size (acres)"></div>
              <div class="mb-2"><input type="text" name="soil_type" class="form-control" placeholder="Soil type"></div>
              <div class="mb-2"><input type="text" name="crop_assignment" class="form-control" placeholder="Current crop assignment"></div>
              <button type="submit" class="btn btn-primary w-100">Add Plot</button>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="content-card">
            <h6 class="mb-3">Plots in <?= e($block['name']) ?></h6>
            <?php if (!$plots): ?>
              <?php render_empty_state('No plots yet. Add one on the left.'); ?>
            <?php else: ?>
              <table class="table-app">
                <thead><tr><th>Name</th><th>Size</th><th>Soil</th><th>Crop</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($plots as $p): ?>
                  <tr>
                    <td><?= e($p['name']) ?></td>
                    <td><?= $p['size'] ? number_format($p['size'], 1) . ' acres' : '-' ?></td>
                    <td><?= e($p['soil_type'] ?: '-') ?></td>
                    <td><?= e($p['crop_assignment'] ?: '-') ?></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delPlot<?= $p['id'] ?>"><i class="bi bi-trash"></i></button>
                    </td>
                  </tr>
                  <?php confirm_delete_modal('delPlot' . $p['id'], base_url('org-admin/farms/plots.php?block_id=' . $blockId), e($p['name']), ['id' => $p['id']]); ?>
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
