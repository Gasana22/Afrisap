<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$fields = [
    'general.site_name' => ['label' => 'Site Name', 'default' => app_config()['app']['name']],
    'general.contact_email' => ['label' => 'Contact Email', 'default' => ''],
    'general.support_phone' => ['label' => 'Support Phone', 'default' => ''],
];

if (is_post() && csrf_verify()) {
    foreach ($fields as $key => $meta) {
        upsert_setting($key, clean_string($_POST[$key] ?? ''), 'general');
    }
    audit_log(null, $admin['id'], 'update', 'system_settings', null, null, ['group' => 'general']);
    session_flash('success', 'General settings saved.');
    redirect('admin/settings/general.php');
}

$values = settings_group('general');

render_header(['title' => 'General Settings', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">General Settings</h4>

      <div class="content-card" style="max-width:640px;">
        <form method="post">
          <?= csrf_field() ?>
          <?php foreach ($fields as $key => $meta): ?>
            <div class="mb-3">
              <label class="form-label"><?= e($meta['label']) ?></label>
              <input type="text" name="<?= e($key) ?>" class="form-control" value="<?= e($values[$key] ?? $meta['default']) ?>">
            </div>
          <?php endforeach; ?>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
