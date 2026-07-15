<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$fields = [
    'email.smtp_host' => 'SMTP Host',
    'email.smtp_port' => 'SMTP Port',
    'email.smtp_username' => 'SMTP Username',
    'email.from_address' => 'From Address',
    'email.from_name' => 'From Name',
];

if (is_post() && csrf_verify()) {
    foreach ($fields as $key => $label) {
        upsert_setting($key, clean_string($_POST[$key] ?? ''), 'email');
    }
    audit_log(null, $admin['id'], 'update', 'system_settings', null, null, ['group' => 'email']);
    session_flash('success', 'Email settings saved.');
    redirect('admin/settings/email.php');
}

$values = settings_group('email');

render_header(['title' => 'Email Settings', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Email Settings</h4>
      <div class="alert alert-info">
        These values document/override platform-level email configuration. Actual sending currently uses the <code>MAIL_*</code> variables in <code>.env</code> - those take precedence until the mailer is wired to read from here.
      </div>

      <div class="content-card" style="max-width:640px;">
        <form method="post">
          <?= csrf_field() ?>
          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">SMTP Host</label>
              <input type="text" name="email.smtp_host" class="form-control" value="<?= e($values['email.smtp_host'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">SMTP Port</label>
              <input type="number" name="email.smtp_port" class="form-control" value="<?= e($values['email.smtp_port'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">SMTP Username</label>
            <input type="text" name="email.smtp_username" class="form-control" value="<?= e($values['email.smtp_username'] ?? '') ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">From Address</label>
              <input type="email" name="email.from_address" class="form-control" value="<?= e($values['email.from_address'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">From Name</label>
              <input type="text" name="email.from_name" class="form-control" value="<?= e($values['email.from_name'] ?? '') ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
