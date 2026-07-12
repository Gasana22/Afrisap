<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Farm Portal self-signup -- the one account-creation path that isn't
// gated behind an existing login. A brand-new Farm Owner creates their own
// organization here; every other tenant user (Farm Manager, Field Worker,
// ...) is invited by that Farm Owner via admin/users.php, and platform
// staff accounts can only ever be created by an existing Super Admin --
// this form has no way to request either.

if (is_logged_in()) {
    redirect('/admin/dashboard.php');
}

$error = flash('error');
$old = ['organization_name' => '', 'name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'organization_name' => trim($_POST['organization_name'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($old['organization_name'] === '' || $old['name'] === '' || $old['email'] === '' || $password === '') {
        $error = 'Farm/organization name, your name, email, and password are all required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } else {
        $existing = db()->prepare('SELECT id FROM users WHERE email = :email');
        $existing->execute(['email' => strtolower($old['email'])]);
        if ($existing->fetchColumn()) {
            $error = 'An account with that email already exists.';
        } else {
            $farmOwnerRole = db()->query("SELECT id FROM roles WHERE slug = 'farm_owner'")->fetch();

            db()->beginTransaction();
            try {
                db()->prepare('INSERT INTO organizations (name, status) VALUES (:name, "active")')
                    ->execute(['name' => $old['organization_name']]);
                $organizationId = (int) db()->lastInsertId();

                db()->prepare(
                    'INSERT INTO users (name, email, phone, password_hash, role_id, organization_id, status, mfa_enabled)
                     VALUES (:name, :email, :phone, :hash, :role_id, :org_id, "active", 0)'
                )->execute([
                    'name' => $old['name'],
                    'email' => strtolower($old['email']),
                    'phone' => $old['phone'] ?: null,
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role_id' => $farmOwnerRole['id'],
                    'org_id' => $organizationId,
                ]);
                $userId = (int) db()->lastInsertId();

                db()->prepare('UPDATE organizations SET owner_user_id = :uid WHERE id = :id')
                    ->execute(['uid' => $userId, 'id' => $organizationId]);

                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

            audit_log('signup', 'organizations', (string) $organizationId, null, ['organization_name' => $old['organization_name'], 'owner_user_id' => $userId]);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $result = attempt_login(strtolower($old['email']), $password, $ip, 'tenant');

            if (!empty($result['mfa_required'])) {
                redirect('/verify-otp.php');
            }

            flash('success', 'Welcome to ' . APP_NAME . '! Your farm account is ready.');
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
    <title>Create your farm account — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="auth-page">
    <?php $authHeadline = 'Your organization, on its own ledger.'; $authCopy = 'Every organization&#8217;s farms, records, and finances stay fully separated from every other organization&#8217;s.'; require __DIR__ . '/includes/auth_panel.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card" style="max-width:420px;">
            <a href="<?= BASE_URL ?>/index.php" class="back-home">&larr; <?= e(APP_NAME) ?></a>
            <h1>Create your farm account</h1>
            <p class="muted">For farm owners setting up a new organization. Already have staff logging in for an existing farm? Ask your Farm Owner to invite you instead.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/signup.php">
                <label for="organization_name">Farm / organization name</label>
                <input type="text" id="organization_name" name="organization_name" value="<?= e($old['organization_name']) ?>" required autofocus>

                <label for="name">Your name</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>

                <label for="phone">Phone (optional)</label>
                <input type="text" id="phone" name="phone" value="<?= e($old['phone']) ?>">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" required>

                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>

                <button type="submit" style="width:100%; justify-content:center;">Create account</button>
            </form>

            <p class="portal-switch">Already have an account? <a href="<?= BASE_URL ?>/login.php">Sign in</a>.</p>
        </div>
    </div>
</body>
</html>
