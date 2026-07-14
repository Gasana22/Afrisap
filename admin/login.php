<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/includes/svg.php';

if (current_admin()) {
    redirect('/admin/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Enter your email and password.';
    } elseif (attempt_login($email, $password)) {
        redirect('/admin/index.php');
    } else {
        $errors[] = 'That email and password don\'t match an admin account.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · Safarisap Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(url('/assets/admin/css/admin.css')) ?>">
</head>
<body>
<div class="login-page">
  <?= login_field_svg() ?>
  <div class="login-card">
    <div class="login-card__brand">
      <div>
        <div class="login-card__wordmark">Safarisap</div>
        <span class="login-card__tagline">Explore. Experience. Belong.</span>
      </div>
    </div>
    <h1>Admin sign in</h1>
    <p class="lead">Manage tours, destinations and experiences.</p>

    <?php if ($errors): ?>
      <div class="flash flash--error"><?= h($errors[0]) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= h(url('/admin/login.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="form-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" autofocus required>
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn--primary">Sign in</button>
    </form>
  </div>
</div>
</body>
</html>
