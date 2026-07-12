<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION['mfa_pending_user_id'])) {
    redirect('/index.php');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    generate_and_send_otp((int) $_SESSION['mfa_pending_user_id']);
    flash('success', 'A new code has been sent.');
    redirect('/verify-otp.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['mfa_pending_user_id'];
    $code = trim($_POST['code'] ?? '');

    $stmt = db()->prepare(
        'SELECT * FROM otp_codes
         WHERE user_id = :user_id AND code = :code AND purpose = "login_mfa"
         AND used_at IS NULL AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId, 'code' => $code]);
    $otp = $stmt->fetch();

    if (!$otp) {
        $error = 'Invalid or expired code.';
    } else {
        db()->prepare('UPDATE otp_codes SET used_at = NOW() WHERE id = :id')
            ->execute(['id' => $otp['id']]);

        $userStmt = db()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.scope AS role_scope
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id'
        );
        $userStmt->execute(['id' => $userId]);
        log_user_in($userStmt->fetch());

        redirect('/admin/dashboard.php');
    }
}

// Dev-only convenience: no SMS/email provider is wired up yet, so surface
// the pending code here instead of making it un-loginnable outside prod.
// Remove this block once real delivery exists.
$devCode = null;
if (APP_DEBUG) {
    $devStmt = db()->prepare(
        'SELECT code FROM otp_codes
         WHERE user_id = :user_id AND purpose = "login_mfa" AND used_at IS NULL AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1'
    );
    $devStmt->execute(['user_id' => $_SESSION['mfa_pending_user_id']]);
    $devCode = $devStmt->fetchColumn() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <h1>Enter verification code</h1>
        <p class="muted">We sent a one-time code to your registered contact method.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($devCode): ?>
            <div class="alert alert-success">Dev mode — no SMS/email provider yet. Your code is <strong><?= e($devCode) ?></strong> (also in storage/logs/otp.log).</div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/verify-otp.php">
            <input type="text" name="code" maxlength="10" class="otp-input" required autofocus>
            <button type="submit">Verify</button>
        </form>
        <form method="POST" action="<?= BASE_URL ?>/verify-otp.php" style="margin-top:0.75rem;">
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="btn" style="background:transparent;color:#2f5233;border:1px solid #2f5233;width:100%;">Resend code</button>
        </form>
    </div>
</body>
</html>
