<?php
/**
 * The dark brand panel shown beside every auth form (login, admin-login,
 * signup, forgot/reset-password, verify-otp). Set $authHeadline and
 * $authCopy before requiring. Hidden below 860px -- see .auth-brand in
 * site.css -- so it never competes with the form on a phone.
 */
?>
<div class="auth-brand">
    <span class="auth-brand-mark"><?= e(APP_NAME) ?></span>
    <div class="auth-brand-copy">
        <h2><?= $authHeadline ?></h2>
        <p><?= $authCopy ?></p>
        <div class="trail-mini">
            <?php $trailActive = 0; require __DIR__ . '/trail_widget.php'; ?>
        </div>
    </div>
</div>
