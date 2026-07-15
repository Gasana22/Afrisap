<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$fields = [
    'integrations.google_maps_key' => 'Google Maps API Key',
    'integrations.openweather_key' => 'OpenWeather API Key',
];

if (is_post() && csrf_verify()) {
    foreach ($fields as $key => $label) {
        $value = $_POST[$key] ?? '';
        if ($value !== '' && $value !== str_repeat('*', 12)) {
            upsert_setting($key, clean_string($value), 'integrations');
        }
    }
    audit_log(null, $admin['id'], 'update', 'system_settings', null, null, ['group' => 'integrations']);
    session_flash('success', 'Integration settings saved.');
    redirect('admin/settings/integrations.php');
}

$values = settings_group('integrations');

function mask_key(string $value): string
{
    if ($value === '') {
        return '';
    }
    $len = strlen($value);

    return $len <= 4 ? str_repeat('*', $len) : str_repeat('*', $len - 4) . substr($value, -4);
}

render_header(['title' => 'Integrations', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Integrations</h4>
      <div class="alert alert-info">
        These are placeholder platform-level API keys for future integrations. Keys are masked below; enter a new value to replace one (leave blank to keep the existing key unchanged).
      </div>

      <div class="content-card" style="max-width:640px;">
        <form method="post">
          <?= csrf_field() ?>
          <?php foreach ($fields as $key => $label): ?>
            <div class="mb-3">
              <label class="form-label"><?= e($label) ?></label>
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted small font-monospace"><?= e(mask_key($values[$key] ?? '') ?: 'Not set') ?></span>
              </div>
              <input type="text" name="<?= e($key) ?>" class="form-control mt-1" placeholder="Enter new key to update...">
            </div>
          <?php endforeach; ?>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
