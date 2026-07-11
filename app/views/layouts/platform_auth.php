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
<body class="platform-body">
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <span class="brand-mark scan-frame d-inline-flex mb-1" style="width:48px;height:48px;font-size:1.5rem;color:var(--platform-slate-light);"><i class="bi bi-shield-lock-fill"></i></span>
            <h4 class="mt-2 mb-0">Afrisap Platform Admin</h4>
            <p class="text-muted small">Super Admin &middot; Platform Manager &middot; Platform Accountant</p>
        </div>
        <?php require __DIR__ . '/../partials/flash.php'; ?>
        <?= $content ?>
        <div class="text-center mt-4">
            <a href="/login" class="small text-muted">Looking for the farm app? Sign in here</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
