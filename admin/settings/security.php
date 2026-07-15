<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$fields = [
    'security.min_password_length' => ['label' => 'Minimum Password Length', 'default' => '8'],
    'security.max_login_attempts' => ['label' => 'Max Login Attempts', 'default' => '5'],
    'security.session_timeout_minutes' => ['label' => 'Session Timeout (minutes)', 'default' => (string) app_config()['session']['timeout_minutes']],
];

if (is_post() && csrf_verify()) {
    foreach ($fields as $key => $meta) {
        upsert_setting($key, clean_string($_POST[$key] ?? ''), 'security');
    }
    audit_log(null, $admin['id'], 'update', 'system_settings', null, null, ['group' => 'security']);
    session_flash('success', 'Security settings saved.');
    redirect('admin/settings/security.php');
}

$values = settings_group('security');

render_header(['title' => 'Security Settings', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Security Settings</h4>

      <div class="content-card" style="max-width:640px;">
        <form method="post">
          <?= csrf_field() ?>
          <?php foreach ($fields as $key => $meta): ?>
            <div class="mb-3">
              <label class="form-label"><?= e($meta['label']) ?></label>
              <input type="number" name="<?= e($key) ?>" class="form-control" value="<?= e($values[$key] ?? $meta['default']) ?>">
            </div>
          <?php endforeach; ?>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
