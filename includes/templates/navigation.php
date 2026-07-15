<?php
/**
 * Top navigation bars for each of the platform's access points.
 */

function render_public_navbar(): void
{
    $links = [
        'about.php' => 'About',
        'features.php' => 'Features',
        'pricing.php' => 'Pricing',
        'blog.php' => 'Blog',
        'contact.php' => 'Contact',
    ];

    echo '<nav class="navbar navbar-expand-lg navbar-public sticky-top">';
    echo '<div class="container">';
    echo '<a class="navbar-brand" href="' . base_url('public/index.php') . '"><i class="bi bi-flower1"></i> Smart Farm Platform</a>';
    echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav"><span class="navbar-toggler-icon"></span></button>';
    echo '<div class="collapse navbar-collapse" id="publicNav">';
    echo '<ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">';
    foreach ($links as $href => $label) {
        $active = str_ends_with($_SERVER['SCRIPT_NAME'], $href) ? ' active' : '';
        echo '<li class="nav-item"><a class="nav-link' . $active . '" href="' . base_url("public/$href") . '">' . e($label) . '</a></li>';
    }
    echo '<li class="nav-item"><a class="nav-link" href="' . base_url('public/login.php') . '">Log In</a></li>';
    echo '<li class="nav-item"><a class="btn btn-primary btn-sm ms-lg-2" href="' . base_url('public/register.php') . '">Start Free Trial</a></li>';
    echo '</ul></div></div></nav>';
}

function render_public_footer_nav(): void
{
    $year = date('Y');
    $urls = [
        'features' => base_url('public/features.php'),
        'pricing' => base_url('public/pricing.php'),
        'demo' => base_url('public/demo.php'),
        'about' => base_url('public/about.php'),
        'blog' => base_url('public/blog.php'),
        'contact' => base_url('public/contact.php'),
        'privacy' => base_url('public/privacy.php'),
        'login' => base_url('public/login.php'),
        'register' => base_url('public/register.php'),
    ];

    echo '<footer class="site-footer"><div class="container"><div class="row g-4">';
    echo '<div class="col-md-4"><h5><i class="bi bi-flower1"></i> Smart Farm Platform</h5>'
        . '<p class="text-muted">Multi-tenant farm management & traceability for modern agribusiness.</p></div>';

    echo '<div class="col-md-2"><h6>Product</h6><ul class="list-unstyled">'
        . '<li><a href="' . $urls['features'] . '">Features</a></li>'
        . '<li><a href="' . $urls['pricing'] . '">Pricing</a></li>'
        . '<li><a href="' . $urls['demo'] . '">Request Demo</a></li></ul></div>';

    echo '<div class="col-md-2"><h6>Company</h6><ul class="list-unstyled">'
        . '<li><a href="' . $urls['about'] . '">About</a></li>'
        . '<li><a href="' . $urls['blog'] . '">Blog</a></li>'
        . '<li><a href="' . $urls['contact'] . '">Contact</a></li></ul></div>';

    echo '<div class="col-md-2"><h6>Legal</h6><ul class="list-unstyled">'
        . '<li><a href="' . $urls['privacy'] . '">Privacy Policy</a></li></ul></div>';

    echo '<div class="col-md-2"><h6>Account</h6><ul class="list-unstyled">'
        . '<li><a href="' . $urls['login'] . '">Log In</a></li>'
        . '<li><a href="' . $urls['register'] . '">Register</a></li></ul></div>';

    echo '</div><hr class="border-secondary">'
        . '<p class="text-muted small mb-0">&copy; ' . $year . ' Smart Farm Platform. All rights reserved.</p>'
        . '</div></footer>';
}

/**
 * Shared top bar used by admin/ and org-admin/ (search, notifications, profile dropdown).
 */
function render_app_topbar(string $context, array $currentUser, ?array $organization = null): void
{
    $orgId = $organization['id'] ?? null;
    $unread = $orgId ? unread_notification_count($orgId, $currentUser['id']) : 0;
    $notifications = $orgId ? recent_notifications($orgId, $currentUser['id']) : [];
    $logoutUrl = base_url($context === 'admin' ? 'admin/logout.php' : 'org-admin/logout.php');
    $profileUrl = $context === 'admin' ? base_url('admin/settings/general.php') : base_url('org-admin/settings/profile.php');
    $name = e($currentUser['name'] ?? 'User');
    $roleLabel = $context === 'admin' ? 'Platform Admin' : e(humanize($currentUser['role'] ?? ''));

    echo '<header class="app-topbar">';
    echo '<button class="sidebar-toggle d-lg-none" type="button" id="sidebarToggle"><i class="bi bi-list"></i></button>';
    echo '<form class="topbar-search d-none d-md-flex" role="search"><i class="bi bi-search"></i><input type="search" placeholder="Search..." aria-label="Search"></form>';
    echo '<div class="topbar-actions ms-auto d-flex align-items-center gap-3">';

    echo '<div class="dropdown">';
    echo '<button class="btn btn-icon position-relative" data-bs-toggle="dropdown" aria-label="Notifications"><i class="bi bi-bell"></i>';
    if ($unread > 0) {
        echo '<span class="badge rounded-pill bg-danger notif-badge">' . $unread . '</span>';
    }
    echo '</button>';
    echo '<div class="dropdown-menu dropdown-menu-end notif-dropdown">';
    echo '<h6 class="dropdown-header">Notifications</h6>';
    if (!$notifications) {
        echo '<span class="dropdown-item-text text-muted small">No notifications yet.</span>';
    }
    foreach ($notifications as $n) {
        echo '<a class="dropdown-item' . ($n['is_read'] ? '' : ' fw-semibold') . '" href="' . e($n['link'] ?? '#') . '">'
            . e($n['title']) . '<div class="small text-muted">' . e($n['message']) . '</div></a>';
    }
    echo '</div></div>';

    echo '<div class="dropdown">';
    echo '<button class="btn btn-user d-flex align-items-center gap-2" data-bs-toggle="dropdown">';
    echo '<span class="avatar-circle">' . e(strtoupper(substr($name, 0, 1))) . '</span>';
    echo '<span class="d-none d-md-flex flex-column text-start lh-sm"><strong>' . $name . '</strong><small class="text-muted">' . $roleLabel . '</small></span>';
    echo '</button>';
    echo '<ul class="dropdown-menu dropdown-menu-end">';
    echo '<li><a class="dropdown-item" href="' . $profileUrl . '"><i class="bi bi-person me-2"></i>Profile</a></li>';
    echo '<li><hr class="dropdown-divider"></li>';
    echo '<li><a class="dropdown-item text-danger" href="' . $logoutUrl . '"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>';
    echo '</ul></div>';

    echo '</div></header>';
}

/**
 * Worker portal top bar - mobile-first, no sidebar. Shows worker name +
 * a logout shortcut, plus the offline banner (toggled via body.is-offline
 * in worker.js) right above it.
 */
function render_worker_topbar(array $worker): void
{
    $name = e($worker['name'] ?? 'Worker');
    $roleLabel = e(humanize((string) ($worker['role'] ?? 'Worker')));

    echo '<div class="offline-banner"><i class="bi bi-wifi-off me-1"></i>You are offline - actions will sync automatically when you reconnect.</div>';
    echo '<header class="worker-topbar">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<div><div class="small opacity-75">Welcome back</div><div class="h5 mb-0">' . $name . '</div><div class="small opacity-75">' . $roleLabel . '</div></div>';
    echo '<a href="' . base_url('worker/logout.php') . '" class="text-white" title="Log out"><i class="bi bi-box-arrow-right fs-3"></i></a>';
    echo '</div></header>';
}

/**
 * Worker portal bottom navigation - fixed mobile tab bar.
 */
function render_worker_bottom_nav(string $activePath): void
{
    $items = [
        'worker/index.php' => ['Home', 'bi-house'],
        'worker/tasks.php' => ['Tasks', 'bi-list-check'],
        'worker/attendance.php' => ['Clock', 'bi-clock'],
        'worker/calendar.php' => ['Calendar', 'bi-calendar3'],
        'worker/profile.php' => ['Profile', 'bi-person'],
    ];

    $activeFile = basename(parse_url($activePath, PHP_URL_PATH) ?: '');

    echo '<nav class="worker-bottom-nav">';
    foreach ($items as $path => [$label, $icon]) {
        $isActive = basename($path) === $activeFile;
        echo '<a href="' . base_url($path) . '" class="' . ($isActive ? 'active' : '') . '">'
            . '<i class="bi ' . $icon . '"></i><span>' . e($label) . '</span></a>';
    }
    echo '</nav>';
}
