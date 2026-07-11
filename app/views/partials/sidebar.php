<?php
use App\Core\Auth;
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$isActive = fn(string $prefix) => str_starts_with($uri, $prefix) ? 'active' : '';
?>
<nav class="sidebar-nav">
    <a class="nav-link <?= $isActive('/') === 'active' && $uri === '/' ? 'active' : '' ?>" href="/">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
    </a>

    <?php if (Auth::hasPermission('farms.view')): ?>
    <a class="nav-link <?= $isActive('/farms') ?>" href="/farms">
        <i class="bi bi-geo-alt"></i> <span>Farm Structure</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('crops.view')): ?>
    <a class="nav-link <?= $isActive('/crops') ?>" href="/crops">
        <i class="bi bi-flower1"></i> <span>Crop Management</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('livestock.view')): ?>
    <a class="nav-link <?= $isActive('/livestock') ?>" href="/livestock">
        <i class="bi bi-piggy-bank"></i> <span>Livestock</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('workers.view')): ?>
    <a class="nav-link <?= $isActive('/workers') ?>" href="/workers">
        <i class="bi bi-people"></i> <span>Workers</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('finance.view')): ?>
    <a class="nav-link <?= $isActive('/finance') ?>" href="/finance">
        <i class="bi bi-cash-coin"></i> <span>Finance</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('procurement.view')): ?>
    <a class="nav-link <?= $isActive('/suppliers') === 'active' || $isActive('/purchase-orders') === 'active' ? 'active' : '' ?>" href="/purchase-orders">
        <i class="bi bi-truck"></i> <span>Procurement</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('inventory.view')): ?>
    <a class="nav-link <?= $isActive('/inventory') ?>" href="/inventory">
        <i class="bi bi-box-seam"></i> <span>Inventory</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('assets.view')): ?>
    <a class="nav-link <?= $isActive('/farm-assets') ?>" href="/farm-assets">
        <i class="bi bi-truck-front"></i> <span>Assets</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('traceability.view')): ?>
    <a class="nav-link <?= $isActive('/traceability') ?>" href="/traceability">
        <i class="bi bi-qr-code"></i> <span>Traceability</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('reports.view')): ?>
    <a class="nav-link <?= $isActive('/reports') ?>" href="/reports">
        <i class="bi bi-bar-chart-line"></i> <span>Reports</span>
    </a>
    <?php $alertCount = \App\Models\AlertEngine::count(Auth::organizationId()); ?>
    <a class="nav-link <?= $isActive('/alerts') ?>" href="/alerts">
        <i class="bi bi-exclamation-triangle"></i> <span>Alerts</span>
        <?php if ($alertCount > 0): ?><span class="badge bg-danger ms-auto"><?= $alertCount ?></span><?php endif; ?>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('farms.view')): ?>
    <a class="nav-link <?= $isActive('/maps') ?>" href="/maps">
        <i class="bi bi-map"></i> <span>Farm Map</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('media.view')): ?>
    <a class="nav-link <?= $isActive('/media') ?>" href="/media">
        <i class="bi bi-folder2-open"></i> <span>Media &amp; Documents</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('team.view')): ?>
    <a class="nav-link <?= $isActive('/team') ?>" href="/team">
        <i class="bi bi-person-plus"></i> <span>Team</span>
    </a>
    <?php endif; ?>
</nav>
