<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner', 'manager']);
$organization = current_organization();
$orgId = (int) $organization['id'];

$settings = json_decode((string) ($organization['settings'] ?? '{}'), true) ?: [];
$primaryColor = $settings['primary_color'] ?? '#2E7D32';

$errors = [];

if (is_post() && csrf_verify()) {
    $newColor = clean_string($_POST['primary_color'] ?? $primaryColor);
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $newColor)) {
        $errors['primary_color'] = 'Please choose a valid color.';
    }

    $logoPath = $organization['logo'] ?? null;
    if (!$errors && !empty($_FILES['logo']['name'])) {
        try {
            $logoPath = handle_upload($_FILES['logo'], $orgId, 'profile');
        } catch (RuntimeException $e) {
            $errors['logo'] = $e->getMessage();
        }
    }

    if (!$errors) {
        $settings['primary_color'] = $newColor;
        $oldValues = ['logo' => $organization['logo'], 'settings' => $organization['settings']];
        db_update('organizations', [
            'logo' => $logoPath,
            'settings' => json_encode($settings),
        ], 'id = :id', ['id' => $orgId]);
        audit_log($orgId, $currentUser['id'], 'update', 'organizations', $orgId, $oldValues, ['logo' => $logoPath, 'primary_color' => $newColor]);
        session_flash('success', 'Branding updated.');
        redirect('org-admin/settings/branding.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Branding', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="mb-3">
        <h4 class="mb-0">Branding</h4>
        <p class="text-muted small mb-0">Customize your organization's logo and primary color.</p>
      </div>

      <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/profile.php') ?>">Profile</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= base_url('org-admin/settings/branding.php') ?>">Branding</a></li>
        <?php if (($currentUser['role'] ?? '') === 'owner'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/users.php') ?>">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/roles.php') ?>">Roles &amp; Permissions</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/integration.php') ?>">Integrations</a></li>
      </ul>

      <div class="content-card" style="max-width:720px;">
        <h5 class="mb-3">Logo &amp; Colors</h5>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Current Logo</label>
            <div class="mb-2">
              <?php if (!empty($organization['logo'])): ?>
                <img src="<?= e(uploaded_file_url($organization['logo'])) ?>" alt="Logo" style="max-height:80px;border-radius:8px;">
              <?php else: ?>
                <span class="text-muted small">No logo uploaded yet.</span>
              <?php endif; ?>
            </div>
            <input type="file" name="logo" accept="image/*" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Primary Color</label>
            <div class="d-flex align-items-center gap-2">
              <input type="color" name="primary_color" class="form-control form-control-color" value="<?= e($primaryColor) ?>" style="width:60px;">
              <span class="text-muted small"><?= e($primaryColor) ?></span>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save Branding</button>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
