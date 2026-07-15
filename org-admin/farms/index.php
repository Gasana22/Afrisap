<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

if (is_post() && ($_POST['_method'] ?? '') === 'DELETE' && csrf_verify()) {
    $id = clean_int($_POST['id'] ?? 0);
    tenant_delete('farms', $orgId, $id);
    audit_log($orgId, $currentUser['id'], 'delete', 'farms', $id);
    session_flash('success', 'Farm deleted.');
    redirect('org-admin/farms/index.php');
}

$farms = tenant_all('farms', $orgId, 'ORDER BY created_at DESC');
$limits = organization_within_plan_limits($organization);

render_header(['title' => 'Farms', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Farms</h4>
          <p class="text-muted small mb-0"><?= $limits['farms']['used'] ?> of <?= $limits['farms']['limit'] ?> farms used on your <?= e(humanize($organization['subscription_plan'])) ?> plan</p>
        </div>
        <?php if ($limits['farms']['ok']): ?>
          <a href="<?= base_url('org-admin/farms/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add New Farm</a>
        <?php else: ?>
          <a href="<?= base_url('public/pricing.php') ?>" class="btn btn-outline-primary">Upgrade plan to add more farms</a>
        <?php endif; ?>
      </div>

      <?php if (!$farms): ?>
        <div class="content-card"><?php render_empty_state('No farms yet. Add your first farm to get started.', 'bi-geo-alt'); ?></div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($farms as $farm): ?>
            <div class="col-md-6 col-xl-4">
              <div class="content-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="h6 mb-0"><?= e($farm['name']) ?></h5>
                  <?php render_status_badge($farm['status']); ?>
                </div>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= e($farm['village'] ?: '-') ?>, <?= e($farm['district'] ?: '-') ?></p>
                <p class="mb-3"><strong><?= number_format((float) $farm['size'], 1) ?></strong> acres</p>
                <div class="d-flex gap-2">
                  <a href="<?= base_url('org-admin/farms/blocks.php?farm_id=' . $farm['id']) ?>" class="btn btn-sm btn-outline-primary flex-fill">Blocks</a>
                  <a href="<?= base_url('org-admin/farms/edit.php?id=' . $farm['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                  <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteFarm<?= $farm['id'] ?>"><i class="bi bi-trash"></i></button>
                </div>
              </div>
            </div>
            <?php confirm_delete_modal('deleteFarm' . $farm['id'], base_url('org-admin/farms/index.php'), e($farm['name']), ['id' => $farm['id']]); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
