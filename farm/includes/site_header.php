<?php
/**
 * Shared header for the public site: index.php, about.php, contact.php,
 * trace.php. Not used by login.php/verify-otp.php -- those keep their own
 * minimal auth-card layout, just with a "back to home" link.
 *
 * Set $pageTitle before requiring.
 */
$pageTitle = $pageTitle ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ? e($pageTitle) . ' — ' : '' ?><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="site-page">
<header class="site-nav">
    <a class="site-brand" href="<?= BASE_URL ?>/index.php"><?= e(APP_NAME) ?></a>
    <nav>
        <a href="<?= BASE_URL ?>/index.php">Home</a>
        <a href="<?= BASE_URL ?>/about.php">About</a>
        <a href="<?= BASE_URL ?>/trace.php">Track a Product</a>
        <a href="<?= BASE_URL ?>/contact.php">Contact</a>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn">Dashboard</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn">Login</a>
        <?php endif; ?>
    </nav>
</header>
<main class="site-main">
