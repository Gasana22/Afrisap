<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/admin/dashboard.php');
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($token === '') {
        $error = 'Missing reset token.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } elseif (!verify_and_reset_password($token, $password)) {
        $error = 'This reset link is invalid or has expired.';
    } else {
        flash('success', 'Password updated. You can now log in.');
        redirect('/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <a href="<?= BASE_URL ?>/index.php" class="back-home">&larr; <?= e(APP_NAME) ?></a>
        <h1>Reset password</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/reset-password.php">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <label for="password">New password</label>
            <input type="password" id="password" name="password" minlength="8" required autofocus>

            <label for="password_confirm">Confirm new password</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>

            <button type="submit">Reset password</button>
        </form>

        <p class="portal-switch"><a href="<?= BASE_URL ?>/login.php">Back to sign in</a></p>
    </div>
</body>
</html>
