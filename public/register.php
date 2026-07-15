<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];

if (is_post() && csrf_verify()) {
    $data = [
        'org_name' => clean_string($_POST['org_name'] ?? ''),
        'name' => clean_string($_POST['name'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'password' => (string) ($_POST['password'] ?? ''),
    ];

    $errors = validate($data, [
        'org_name' => 'required|max:255',
        'name' => 'required|max:255',
        'email' => 'required|email',
        'password' => 'required|min:8',
    ]);

    if (!$errors && db_one('SELECT id FROM users WHERE email = :email', ['email' => $data['email']])) {
        $errors['email'] = 'An account with this email already exists. Please log in instead.';
    }

    if (!$errors) {
        db()->beginTransaction();
        try {
            $userId = db_insert('users', [
                'email' => $data['email'],
                'password_hash' => hash_password($data['password']),
                'name' => $data['name'],
                'status' => 'active',
            ]);

            $slug = generate_unique_slug('organizations', $data['org_name']);

            $orgId = db_insert('organizations', [
                'name' => $data['org_name'],
                'slug' => $slug,
                'email' => $data['email'],
                'subscription_plan' => 'free',
                'subscription_status' => 'trial',
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
            ]);

            db_insert('organization_users', [
                'organization_id' => $orgId,
                'user_id' => $userId,
                'role' => 'owner',
                'status' => 'active',
            ]);

            db()->commit();

            audit_log($orgId, $userId, 'create', 'organizations', $orgId);

            $user = db_one('SELECT * FROM users WHERE id = :id', ['id' => $userId]);
            auth_login_org_user($user, $orgId);
            session_flash('success', 'Welcome to Smart Farm Platform! Your 14-day free trial has started.');
            redirect('org-admin/index.php');
        } catch (Throwable $e) {
            db()->rollBack();
            app_log_error('Registration failed: ' . $e->getMessage());
            $errors['general'] = 'Something went wrong creating your account. Please try again.';
        }
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Start Your Free Trial', 'context' => 'auth']);
?>
<div class="container">
  <div class="auth-card">
    <div class="text-center mb-4">
      <a href="<?= base_url('public/index.php') ?>" class="navbar-brand fw-bold text-primary"><i class="bi bi-flower1"></i> Smart Farm Platform</a>
    </div>
    <h4 class="mb-1">Start your 14-day free trial</h4>
    <p class="text-muted small mb-3">No credit card required.</p>
    <?php render_alerts(); ?>

    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Organization / Farm Name</label>
        <input type="text" name="org_name" class="form-control" required value="<?= e($_POST['org_name'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Your Full Name</label>
        <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" minlength="8" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Create My Account</button>
    </form>
    <p class="text-center mt-3 small text-muted">Already have an account? <a href="<?= base_url('public/login.php') ?>">Log in</a></p>
  </div>
</div>
<?php render_footer(['context' => 'auth']); ?>
