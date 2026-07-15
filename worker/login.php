<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_worker()) {
    redirect('worker/index.php');
}

$errors = [];

if (is_post() && csrf_verify()) {
    $email = clean_string($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $user = auth_attempt($email, $password);
    $worker = null;

    if ($user) {
        $worker = db_one(
            'SELECT w.*, ou.organization_id FROM workers w
             JOIN organization_users ou ON ou.user_id = w.user_id AND ou.organization_id = w.organization_id
             WHERE w.user_id = :user_id AND w.status = "active" AND ou.role = "worker" AND ou.status = "active"',
            ['user_id' => $user['id']]
        );
    }

    if (!$worker) {
        $errors[] = 'Invalid email or password.';
    } else {
        auth_login_worker($user, (int) $worker['organization_id'], (int) $worker['id']);
        redirect('worker/index.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Worker Login', 'context' => 'auth']);
?>
<div class="container">
  <div class="auth-card">
    <div class="text-center mb-4">
      <span class="navbar-brand fw-bold text-primary"><i class="bi bi-person-workspace"></i> Worker Portal</span>
    </div>
    <h4 class="mb-3">Field Worker Login</h4>
    <?php render_alerts(); ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Log In</button>
    </form>
    <p class="text-center mt-3 small text-muted">Farm manager? <a href="<?= base_url('public/login.php') ?>">Log in here</a></p>
    <p class="text-center mt-2 small text-muted">Demo: worker@greenvalley.test / Password123!</p>
  </div>
</div>
<?php render_footer(['context' => 'auth']); ?>
