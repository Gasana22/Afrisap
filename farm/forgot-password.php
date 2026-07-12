<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Farm Portal only -- request_password_reset() scopes its lookup to tenant
// accounts, so this can't be used to reset a platform (Admin Portal)
// account's password.

if (is_logged_in()) {
    redirect('/admin/dashboard.php');
}

$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email !== '') {
        request_password_reset($email);
    }
    // Same message whether or not the account exists -- see
    // request_password_reset()'s docblock for why.
    flash('success', 'If that email belongs to a farm account, a reset link has been sent.');
    redirect('/forgot-password.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="auth-page">
    <?php $authHeadline = 'Locked out happens.'; $authCopy = 'We&#8217;ll email a one-time link to the address on your farm account.'; require __DIR__ . '/includes/auth_panel.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card">
            <a href="<?= BASE_URL ?>/index.php" class="back-home">&larr; <?= e(APP_NAME) ?></a>
            <h1>Forgot password</h1>
            <p class="muted">Enter the email on your farm account and we'll send a reset link.</p>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/forgot-password.php">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>

                <button type="submit" style="width:100%; justify-content:center;">Send reset link</button>
            </form>

            <p class="portal-switch"><a href="<?= BASE_URL ?>/login.php">Back to sign in</a></p>
        </div>
    </div>
</body>
</html>
