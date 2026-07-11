<?php
use App\Core\Auth;
$navUser = $currentUser ?? Auth::user();
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : '' ?>Afrisap SFMTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <i class="bi bi-tree-fill"></i> <span>Afrisap SFMTP</span>
        </div>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="themeToggle" title="Toggle theme">
                    <i class="bi bi-moon-stars"></i>
                </button>
                <?php if ($navUser): ?>
                <?php
                    $unreadCount = \App\Models\Notification::unreadCount((int) $navUser['id']);
                    $recentNotifications = \App\Models\Notification::forUser((int) $navUser['id'], 8);
                ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong class="small">Notifications</strong>
                            <a href="/notifications" class="small">View all</a>
                        </div>
                        <?php if (empty($recentNotifications)): ?>
                        <div class="px-3 py-3 text-muted small">No notifications yet.</div>
                        <?php endif; ?>
                        <?php foreach ($recentNotifications as $n): ?>
                        <a href="<?= $n['link'] ? htmlspecialchars($n['link']) : '/notifications' ?>" class="dropdown-item small py-2 border-bottom <?= $n['is_read'] ? '' : 'bg-light' ?>">
                            <div class="fw-<?= $n['is_read'] ? 'normal' : 'bold' ?>"><?= htmlspecialchars($n['title']) ?></div>
                            <div class="text-muted"><?= htmlspecialchars($n['message']) ?></div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($navUser): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($navUser['name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($navUser['role_name']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/profile">Profile</a></li>
                        <li>
                            <form method="post" action="/logout">
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
