<?php
use App\Core\Auth;
$navUser = $currentUser ?? Auth::user();
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
    (function () {
        try {
            document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('sfmtp_theme') || 'light');
        } catch (e) {}
    })();
    </script>
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : '' ?>Afrisap Platform Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar platform-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <span class="brand-mark scan-frame"><i class="bi bi-shield-lock-fill"></i></span> <span>Platform Admin</span>
        </div>
        <?php require __DIR__ . '/../partials/platform_sidebar.php'; ?>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <span class="platform-badge ms-2 d-none d-md-inline">Platform</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="themeToggle" title="Toggle theme">
                    <i class="bi bi-moon-stars"></i>
                </button>
                <?php if ($navUser): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($navUser['name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($navUser['role_name']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="/platform/logout">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="dropdown-item" type="submit">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="app-content">
            <?php require __DIR__ . '/../partials/flash.php'; ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
