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

<div class="site-main">
    <section class="section reveal" style="padding-bottom: 2.5rem;">
        <div class="section-head" style="max-width: 68ch;">
            <span class="eyebrow">About <?= e(APP_NAME) ?></span>
            <h1>Built for the office behind the farm, not just the field.</h1>
            <p>A multi-tenant platform for farm owners and cooperatives to run every part of their operation from one place: farm structure, crop cycles, livestock, workers, finance, procurement, inventory, assets, and traceability.</p>
            <p>Each organization's data is fully isolated from every other organization's — farms, records, and finances stay private to the team that owns them. Platform administrators keep the system running without ever seeing into any organization's day-to-day operations.</p>
        </div>
    </section>
</div>

<div class="ledger-strip">
    <div class="ledger-strip-inner">
        <?php foreach ($stats as $label => $value): ?>
            <div class="ledger-item">
                <div class="ledger-number tnum" data-count="<?= $value ?>">0</div>
                <div class="ledger-label"><?= e($label) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="site-main">
    <section class="section reveal">
        <div class="section-head">
            <h2>How traceability works</h2>
            <p>The same journey, every time — from the moment a crop cycle or an animal is created to the moment a buyer checks where it came from.</p>
        </div>
        <div class="hero-trail" style="background: var(--surface); border-color: var(--line); max-width: 640px;" data-reveal-trail>
            <div class="hero-trail-label" style="color: var(--muted);">A batch's journey</div>
            <?php $trailActive = 4; require __DIR__ . '/includes/trail_widget.php'; ?>
        </div>
        <p style="max-width: 68ch; margin-top: 1.75rem;">Every crop cycle and every animal is automatically assigned a trace batch the moment it's created. Farm teams generate a QR code for that batch, attach certificates and other documents, log approvals, and record each stage of the product's journey — storage, processing, packaging, distribution, delivery.</p>
        <p style="max-width: 68ch;">Anyone who scans the QR code — or enters its code on the <a href="<?= BASE_URL ?>/trace.php">Track a Product</a> page — sees where that batch came from and where it's been, without needing an account.</p>
    </section>
</div>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
