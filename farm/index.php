<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Public homepage -- no auth-check.php on purpose, this is the public site,
// separate from the admin panel under /admin. Logged-in visitors aren't
// redirected away; they just get a "Dashboard" link in the nav instead.

$companyStmt = db()->prepare('SELECT setting_value FROM settings WHERE organization_id IS NULL AND setting_key = "company_name"');
$companyStmt->execute();
$companyName = $companyStmt->fetchColumn() ?: APP_NAME;

// Aggregate, non-sensitive counts only -- individual farms/organizations are
// tenant-private data and never surfaced on the public site.
$stats = [
    'Organizations' => (int) db()->query('SELECT COUNT(*) FROM organizations')->fetchColumn(),
    'Farms managed' => (int) db()->query('SELECT COUNT(*) FROM farms')->fetchColumn(),
    'Crop types tracked' => (int) db()->query('SELECT COUNT(*) FROM crop_types')->fetchColumn(),
    'Traceable batches' => (int) db()->query('SELECT COUNT(*) FROM trace_batches')->fetchColumn(),
];

$pageTitle = 'Home';
require __DIR__ . '/includes/site_header.php';
?>

<section class="hero">
    <h1><?= e($companyName) ?> Farm Management</h1>
    <p>A multi-tenant platform for running farm operations end to end -- crop cycles, livestock, workers, finance, procurement, inventory, assets, and full farm-to-buyer traceability.</p>
    <div class="hero-actions">
        <a href="<?= BASE_URL ?>/signup.php" class="btn">Start Your Farm Account</a>
        <a href="<?= BASE_URL ?>/trace.php" class="btn btn-outline">Track a Product</a>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Login</a>
    </div>
</section>

<section class="stat-band">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat-tile">
            <div class="stat-number"><?= $value ?></div>
            <div class="stat-label"><?= e($label) ?></div>
        </div>
    <?php endforeach; ?>
</section>

<section class="section">
    <h2>What the platform covers</h2>
    <div class="card-grid">
        <div class="card"><h3>Farm Structure</h3><p>Farms, blocks and plots, organized per organization.</p></div>
        <div class="card"><h3>Crop Cycles</h3><p>Planning through harvest and sale, with inputs, monitoring, and yield forecasts.</p></div>
        <div class="card"><h3>Livestock</h3><p>Vaccinations, feedings, weights, treatments, breeding, and production records.</p></div>
        <div class="card"><h3>Workers</h3><p>Attendance, task assignment, and payroll.</p></div>
        <div class="card"><h3>Finance &amp; Procurement</h3><p>Income, expenses, suppliers, and purchase orders.</p></div>
        <div class="card"><h3>Traceability</h3><p>Every crop cycle and animal gets a QR-code batch that buyers can scan to see its journey.</p></div>
    </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
