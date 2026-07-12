<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Public page -- no auth-check.php on purpose.

$stats = [
    'Organizations onboarded' => (int) db()->query('SELECT COUNT(*) FROM organizations')->fetchColumn(),
    'Farms managed' => (int) db()->query('SELECT COUNT(*) FROM farms')->fetchColumn(),
    'Crop cycles run' => (int) db()->query('SELECT COUNT(*) FROM crop_cycles')->fetchColumn(),
    'Animals recorded' => (int) db()->query('SELECT COUNT(*) FROM animals')->fetchColumn(),
    'Harvests logged' => (int) db()->query('SELECT COUNT(*) FROM harvests')->fetchColumn(),
    'Traceable batches issued' => (int) db()->query('SELECT COUNT(*) FROM trace_batches')->fetchColumn(),
];

$pageTitle = 'About';
require __DIR__ . '/includes/site_header.php';
?>

<section class="section">
    <h1>About <?= e(APP_NAME) ?></h1>
    <p>Afrisap Farm Management is a multi-tenant platform built for farm owners and cooperatives to run every part of their operation from one place: farm structure, crop cycles, livestock, workers, finance, procurement, inventory, assets, and traceability.</p>
    <p>Each organization's data is fully isolated from every other organization's -- farms, records, and finances stay private to the team that owns them. Platform administrators can support every tenant without ever mixing their data together.</p>
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
    <h2>How traceability works</h2>
    <p>Every crop cycle and every animal is automatically assigned a trace batch the moment it's created. Farm teams can generate a QR code for that batch, attach certificates and other documents, log approvals, and record each stage of the product's journey -- storage, processing, packaging, distribution, delivery.</p>
    <p>Anyone who scans the QR code (or enters its code on the <a href="<?= BASE_URL ?>/trace.php">Track a Product</a> page) sees where that batch came from and where it's been, without needing an account.</p>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
