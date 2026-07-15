<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$admin = require_platform_admin();

$plans = db_all('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC');

$errors = [];
$input = [
    'name' => '',
    'email' => '',
    'subscription_plan' => 'free',
    'subscription_status' => 'trial',
    'owner_name' => '',
    'owner_email' => '',
    'owner_password' => '',
];

if (is_post() && csrf_verify()) {
    $input = [
        'name' => clean_string($_POST['name'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'subscription_plan' => clean_string($_POST['subscription_plan'] ?? 'free'),
        'subscription_status' => clean_string($_POST['subscription_status'] ?? 'trial'),
        'owner_name' => clean_string($_POST['owner_name'] ?? ''),
        'owner_email' => clean_string($_POST['owner_email'] ?? ''),
        'owner_password' => (string) ($_POST['owner_password'] ?? ''),
    ];

    $errors = validate($input, [
        'name' => 'required|max:255',
        'email' => 'email',
        'subscription_plan' => 'required|in:free,basic,professional,enterprise',
        'subscription_status' => 'required|in:active,suspended,expired,trial',
        'owner_name' => 'required|max:255',
        'owner_email' => 'required|email',
        'owner_password' => 'required|min:8',
    ]);

    if (!$errors && db_one('SELECT id FROM users WHERE email = :email', ['email' => $input['owner_email']])) {
        $errors['owner_email'] = 'A user with this email already exists.';
    }

    if (!$errors) {
        db()->beginTransaction();
        try {
            $slug = generate_unique_slug('organizations', $input['name']);

            $orgId = db_insert('organizations', [
                'name' => $input['name'],
                'slug' => $slug,
                'email' => $input['email'] ?: null,
                'subscription_plan' => $input['subscription_plan'],
                'subscription_status' => $input['subscription_status'],
                'trial_ends_at' => $input['subscription_status'] === 'trial' ? date('Y-m-d H:i:s', strtotime('+14 days')) : null,
            ]);

            $userId = db_insert('users', [
                'email' => $input['owner_email'],
                'password_hash' => hash_password($input['owner_password']),
                'name' => $input['owner_name'],
                'status' => 'active',
            ]);

            db_insert('organization_users', [
                'organization_id' => $orgId,
                'user_id' => $userId,
                'role' => 'owner',
                'status' => 'active',
            ]);

            db()->commit();

            audit_log($orgId, $admin['id'], 'create', 'organizations', $orgId, null, $input);
            session_flash('success', 'Tenant "' . $input['name'] . '" registered successfully.');
            redirect('admin/tenants/index.php');
        } catch (Throwable $e) {
            db()->rollBack();
            app_log_error('Tenant creation failed: ' . $e->getMessage());
            $errors['general'] = 'Something went wrong registering this tenant. Please try again.';
        }
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Register Tenant', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:760px;">
        <h5 class="mb-3">Register New Tenant</h5>
        <form method="post">
          <?= csrf_field() ?>

          <h6 class="text-muted mb-2">Organization Details</h6>
          <div class="mb-3">
            <label class="form-label">Organization Name</label>
            <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Contact Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($input['email']) ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Subscription Plan</label>
              <select name="subscription_plan" class="form-select">
                <?php foreach ($plans as $plan): ?>
                  <option value="<?= e($plan['slug']) ?>" <?= $input['subscription_plan'] === $plan['slug'] ? 'selected' : '' ?>><?= e($plan['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Subscription Status</label>
              <select name="subscription_status" class="form-select">
                <?php foreach (['trial', 'active', 'suspended', 'expired'] as $s): ?>
                  <option value="<?= $s ?>" <?= $input['subscription_status'] === $s ? 'selected' : '' ?>><?= e(humanize($s)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <hr>
          <h6 class="text-muted mb-2">Owner Account</h6>
          <p class="small text-muted">This creates the first login for the tenant, with the "owner" role.</p>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Owner Full Name</label>
              <input type="text" name="owner_name" class="form-control" required value="<?= e($input['owner_name']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Owner Email</label>
              <input type="email" name="owner_email" class="form-control" required value="<?= e($input['owner_email']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Owner Password</label>
            <input type="password" name="owner_password" class="form-control" minlength="8" required>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Register Tenant</button>
            <a href="<?= base_url('admin/tenants/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>
