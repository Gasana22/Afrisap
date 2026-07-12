<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Farm Portal login -- for Farm Owners and their own tenant staff, who use
// the site to manage their own farms. Platform staff (Super Admin, Manager,
// Accountant, who keep the platform itself operational) use admin-login.php
// instead; attempt_login()'s scope check keeps the two logins from crossing.

if (is_logged_in()) {
    redirect('/admin/dashboard.php');
}

$error = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $result = attempt_login($email, $password, $ip, 'tenant');

        if (!$result['ok']) {
            $error = $result['error'];
        } elseif ($result['mfa_required']) {
            redirect('/verify-otp.php');
        } else {
            redirect('/admin/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farm Portal Login — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <a href="<?= BASE_URL ?>/index.php" class="back-home">&larr; <?= e(APP_NAME) ?></a>
        <h1>Farm Portal</h1>
        <p class="muted">Sign in to manage your own farms, crops, livestock, workers, and records.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/login.php">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Sign in</button>
        </form>
        <p class="portal-switch"><a href="<?= BASE_URL ?>/forgot-password.php">Forgot your password?</a></p>

        <p class="portal-switch">New farm/organization? <a href="<?= BASE_URL ?>/signup.php">Create an account</a>.</p>
        <p class="portal-switch">Platform staff (Super Admin, Manager, Accountant)? <a href="<?= BASE_URL ?>/admin-login.php">Sign in to the Admin Portal</a>.</p>
    </div>
</body>
</html>
