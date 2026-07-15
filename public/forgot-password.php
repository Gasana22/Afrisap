<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$isResetMode = isset($_GET['token'], $_GET['action']) && $_GET['action'] === 'reset';
$token = clean_string($_GET['token'] ?? '');
$resetDone = false;
$requestSent = false;

if ($isResetMode) {
    // ---- Step 2: set a new password using the emailed token ----
    if (is_post() && csrf_verify()) {
        $postedToken = clean_string($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        $errors = validate(['password' => $password], ['password' => 'required|min:8']);

        if (!$errors && $password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (!$errors) {
            $reset = db_one(
                'SELECT * FROM password_resets WHERE token = :token AND used_at IS NULL AND expires_at > NOW()',
                ['token' => $postedToken]
            );

            if (!$reset) {
                $errors['general'] = 'This password reset link is invalid or has expired. Please request a new one.';
            } else {
                db_update('users', ['password_hash' => hash_password($password)], 'id = :id', ['id' => $reset['user_id']]);
                db_update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $reset['id']]);
                $resetDone = true;
            }
        }

        $token = $postedToken;
    } elseif (is_post()) {
        $errors[] = 'Your session expired, please try again.';
    }
} else {
    // ---- Step 1: request a reset link by email ----
    if (is_post() && csrf_verify()) {
        $email = clean_string($_POST['email'] ?? '');

        $errors = validate(['email' => $email], ['email' => 'required|email']);

        if (!$errors) {
            $user = db_one('SELECT * FROM users WHERE email = :email', ['email' => $email]);

            if ($user) {
                $resetToken = bin2hex(random_bytes(32));
                db_insert('password_resets', [
                    'user_id' => $user['id'],
                    'token' => $resetToken,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                ]);

                require_once ROOT_PATH . '/includes/mailer.php';
                $resetLink = base_url('public/forgot-password.php?token=' . $resetToken . '&action=reset');
                send_mail(
                    $user['email'],
                    $user['name'],
                    'Reset your Smart Farm Platform password',
                    '<p>We received a request to reset your password.</p>'
                    . '<p><a href="' . e($resetLink) . '">Click here to set a new password</a> (link expires in 1 hour).</p>'
                    . '<p>If you did not request this, you can safely ignore this email.</p>'
                );
            }

            // Always the same response, whether or not the email was found.
            $requestSent = true;
        }
    } elseif (is_post()) {
        $errors[] = 'Your session expired, please try again.';
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Reset Password', 'context' => 'auth']);
?>
<div class="container">
  <div class="auth-card">
    <div class="text-center mb-4">
      <a href="<?= base_url('public/index.php') ?>" class="navbar-brand fw-bold text-primary"><i class="bi bi-flower1"></i> Smart Farm Platform</a>
    </div>

    <?php if ($isResetMode): ?>
      <h4 class="mb-3">Set a new password</h4>
      <?php render_alerts(); ?>

      <?php if ($resetDone): ?>
        <div class="alert alert-success">Your password has been updated. You can now log in.</div>
        <a href="<?= base_url('public/login.php') ?>" class="btn btn-primary w-100">Go to Log In</a>
      <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control" minlength="8" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="password_confirm" class="form-control" minlength="8" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Update Password</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <h4 class="mb-1">Forgot your password?</h4>
      <p class="text-muted small mb-3">Enter your email and we'll send you a link to reset it.</p>
      <?php render_alerts(); ?>

      <?php if ($requestSent): ?>
        <div class="alert alert-success">If that email exists in our system, we've sent a reset link to it. Please check your inbox.</div>
        <a href="<?= base_url('public/login.php') ?>" class="btn btn-outline-primary w-100">Back to Log In</a>
      <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
        <p class="text-center mt-3 small text-muted"><a href="<?= base_url('public/login.php') ?>">Back to Log In</a></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php render_footer(['context' => 'auth']); ?>
