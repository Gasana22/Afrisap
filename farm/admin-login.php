<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Admin Portal login -- for platform staff (Super Admin, Manager,
// Accountant) who keep the platform itself operational: oversight across
// every tenant, platform-wide finance visibility, and platform user
// management. This is deliberately a separate login door from login.php
// (the Farm Portal, for Farm Owners and their own tenant staff) even
// though both land in the same admin/ panel afterward -- the panel already
// adapts per request based on is_platform_user().

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
        $result = attempt_login($email, $password, $ip, 'platform');

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
    <title>Admin Portal Login — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <a href="<?= BASE_URL ?>/index.php" class="back-home">&larr; <?= e(APP_NAME) ?></a>
        <h1>Admin Portal</h1>
        <p class="muted">For Super Admins, Managers, and Accountants who keep the platform running.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/admin-login.php">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Sign in</button>
        </form>

        <p class="portal-switch">Managing your own farm? <a href="<?= BASE_URL ?>/login.php">Sign in to the Farm Portal</a>.</p>
    </div>
</body>
</html>
