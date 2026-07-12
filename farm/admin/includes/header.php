<?php
/**
 * Include after admin/includes/auth-check.php.
 * Set $pageTitle and optionally $activePage (matches the nav hrefs below)
 * before requiring this file.
 */
$user = current_user();
$activePage = $activePage ?? '';

$unreadStmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = 0');
$unreadStmt->execute(['id' => $user['id'] ?? 0]);
$unreadCount = (int) $unreadStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand"><?= e(APP_NAME) ?></div>
        <div class="portal-badge"><?= is_platform_user() ? 'Admin Portal' : 'Farm Portal' ?></div>
        <nav>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/farms.php" class="<?= $activePage === 'farms' ? 'active' : '' ?>">Farms</a>
            <a href="<?= BASE_URL ?>/admin/crops.php" class="<?= $activePage === 'crops' ? 'active' : '' ?>">Crops</a>
            <a href="<?= BASE_URL ?>/admin/crop-types.php" class="<?= $activePage === 'crop-types' ? 'active' : '' ?>">Crop Types</a>
            <a href="<?= BASE_URL ?>/admin/seasons.php" class="<?= $activePage === 'seasons' ? 'active' : '' ?>">Seasons</a>
            <a href="<?= BASE_URL ?>/admin/livestock.php" class="<?= $activePage === 'livestock' ? 'active' : '' ?>">Livestock</a>
            <a href="<?= BASE_URL ?>/admin/workers.php" class="<?= $activePage === 'workers' ? 'active' : '' ?>">Workers</a>
            <a href="<?= BASE_URL ?>/admin/finance.php" class="<?= $activePage === 'finance' ? 'active' : '' ?>">Finance</a>
            <a href="<?= BASE_URL ?>/admin/suppliers.php" class="<?= $activePage === 'suppliers' ? 'active' : '' ?>">Suppliers</a>
            <a href="<?= BASE_URL ?>/admin/purchase-orders.php" class="<?= $activePage === 'purchase-orders' ? 'active' : '' ?>">Purchase Orders</a>
            <a href="<?= BASE_URL ?>/admin/inventory.php" class="<?= $activePage === 'inventory' ? 'active' : '' ?>">Inventory</a>
            <a href="<?= BASE_URL ?>/admin/assets.php" class="<?= $activePage === 'assets' ? 'active' : '' ?>">Assets</a>
            <a href="<?= BASE_URL ?>/admin/traceability.php" class="<?= $activePage === 'traceability' ? 'active' : '' ?>">Traceability</a>
            <a href="<?= BASE_URL ?>/admin/media.php" class="<?= $activePage === 'media' ? 'active' : '' ?>">Media</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="<?= $activePage === 'users' ? 'active' : '' ?>">Users</a>
        </nav>
    </aside>
    <div class="main">
        <div class="topbar">
            <a href="<?= BASE_URL ?>/admin/notifications.php" style="color:#2f5233; text-decoration:none;">
                Notifications<?= $unreadCount ? ' (' . $unreadCount . ')' : '' ?>
            </a>
            <span><?= e($user['name'] ?? '') ?> · <?= e($_SESSION['role_slug'] ?? '') ?><?= is_platform_user() ? '' : ' · ' . e(current_organization_name() ?? '') ?></span>
            <form method="POST" action="<?= BASE_URL ?>/logout.php">
                <button type="submit" class="link">Log out</button>
            </form>
        </div>
        <div class="content">
