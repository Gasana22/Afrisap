<?php
use App\Core\Auth;
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$isActive = fn(string $prefix) => str_starts_with($uri, $prefix) ? 'active' : '';
?>
<nav class="sidebar-nav">
    <a class="nav-link <?= $isActive('/platform') === 'active' && $uri === '/platform' ? 'active' : '' ?>" href="/platform">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
    </a>

    <?php if (Auth::hasPermission('organizations.view')): ?>
    <a class="nav-link <?= $isActive('/platform/organizations') ?>" href="/platform/organizations">
        <i class="bi bi-buildings"></i> <span>Organizations</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('users.view')): ?>
    <a class="nav-link <?= $isActive('/platform/users') ?>" href="/platform/users">
        <i class="bi bi-person-badge"></i> <span>Platform Staff</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('roles.view')): ?>
    <a class="nav-link <?= $isActive('/platform/roles') ?>" href="/platform/roles">
        <i class="bi bi-shield-lock"></i> <span>Roles &amp; Permissions</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('settings.view')): ?>
    <a class="nav-link <?= $isActive('/platform/settings') ?>" href="/platform/settings">
        <i class="bi bi-gear"></i> <span>Settings</span>
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('audit.view')): ?>
    <a class="nav-link <?= $isActive('/platform/audit-logs') ?>" href="/platform/audit-logs">
        <i class="bi bi-clipboard-data"></i> <span>Audit Log</span>
    </a>
    <?php endif; ?>
</nav>
