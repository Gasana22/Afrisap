<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : '' ?>Product Traceability · Afrisap SFMTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<header class="border-bottom py-3 mb-4">
    <div class="container">
        <span class="fw-bold"><span class="brand-mark scan-frame d-inline-flex align-middle" style="width:26px;height:26px;font-size:0.85rem;color:var(--brand-forest-light);"><i class="bi bi-tree-fill"></i></span> Afrisap SFMTP</span>
        <span class="text-muted small">&middot; Product Traceability</span>
    </div>
</header>
<main class="container pb-5">
    <?= $content ?>
</main>
<footer class="text-center text-muted small py-4">
    Verified farm-to-table traceability record. Every field shown here is sourced from the farm's own operational records.
</footer>
</body>
</html>
