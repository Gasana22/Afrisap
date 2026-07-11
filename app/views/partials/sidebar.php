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

    <?php if (Auth::hasPermission('users.view') || Auth::hasPermission('roles.view') || Auth::hasPermission('settings.view') || Auth::hasPermission('audit.view')): ?>
    <div class="nav-section-label">Admin Panel</div>
    <?php if (Auth::hasPermission('users.view')): ?>
    <a class="nav-link <?= $isActive('/admin/users') ?>" href="/admin/users"><i class="bi bi-person-badge"></i> <span>Users</span></a>
    <?php endif; ?>
    <?php if (Auth::hasPermission('roles.view')): ?>
    <a class="nav-link <?= $isActive('/admin/roles') ?>" href="/admin/roles"><i class="bi bi-shield-lock"></i> <span>Roles &amp; Permissions</span></a>
    <?php endif; ?>
    <?php if (Auth::hasPermission('settings.view')): ?>
    <a class="nav-link <?= $isActive('/admin/settings') ?>" href="/admin/settings"><i class="bi bi-gear"></i> <span>Settings</span></a>
    <?php endif; ?>
    <?php if (Auth::hasPermission('audit.view')): ?>
    <a class="nav-link <?= $isActive('/admin/audit-logs') ?>" href="/admin/audit-logs"><i class="bi bi-clipboard-data"></i> <span>Audit Log</span></a>
    <?php endif; ?>
    <?php endif; ?>
</nav>
