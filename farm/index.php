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
    <div class="hero-inner">
        <div>
            <span class="hero-eyebrow">Farm management &amp; traceability</span>
            <h1><?= e($companyName) ?> runs the farm, and <em>proves</em> where it came from.</h1>
            <p class="lede">One place for crop cycles, livestock, workers, finance, procurement, inventory and assets across every farm your organization runs — with a QR trail a buyer can follow back to the plot it grew on.</p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/signup.php" class="btn btn-on-dark">Start your farm account</a>
                <a href="<?= BASE_URL ?>/trace.php" class="btn btn-ghost-on-dark">Track a product &rarr;</a>
            </div>
        </div>
        <div class="hero-trail" data-reveal-trail>
            <div class="hero-trail-label">Batch CYC-2C51 &middot; live example</div>
            <?php $trailActive = 4; require __DIR__ . '/includes/trail_widget.php'; ?>
        </div>
    </div>
</section>

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
            <h2>Everything a farm office keeps track of</h2>
            <p>Structured for one organization or fifty — each with its own farms, staff, and books, fully separated from the others.</p>
        </div>
        <div class="module-list">
            <div class="module-featured">
                <span class="module-tag">The signature feature</span>
                <h3>Traceability, built in from day one</h3>
                <p>Every crop cycle and every animal gets a trace batch automatically. Generate a QR code, log approvals and journey stages, and let anyone scan it to see exactly where a product came from — no account required.</p>
            </div>
            <div class="module-row">
                <div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6"/><path d="M5 9v10h14V9"/><path d="M9 21V13h6v8"/></svg></div>
                <div>
                    <h3>Farm structure</h3>
                    <p>Farms, blocks, and plots, organized per organization.</p>
                </div>
            </div>
            <div class="module-row">
                <div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-4.5 4-4.5 11 0 15 4.5-4 4.5-11 0-15z"/><path d="M12 18v3"/></svg></div>
                <div>
                    <h3>Crop cycles</h3>
                    <p>Planning through harvest and sale, with inputs, monitoring, and yield forecasts.</p>
                </div>
            </div>
            <div class="module-row">
                <div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="10" r="3"/><circle cx="17" cy="8" r="2.3"/><path d="M3 20c0-3 2.5-5 5-5s5 2 5 5"/><path d="M14 20c0-2.3 1.6-4 3.5-4s3.5 1.7 3.5 4"/></svg></div>
                <div>
                    <h3>Livestock</h3>
                    <p>Vaccinations, feedings, weights, treatments, breeding, and production records.</p>
                </div>
            </div>
            <div class="module-row">
                <div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="3.5"/><path d="M5 21c0-4 3-6.5 7-6.5s7 2.5 7 6.5"/></svg></div>
                <div>
                    <h3>Workers</h3>
                    <p>Attendance, task assignment, and payroll.</p>
                </div>
            </div>
            <div class="module-row">
                <div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/></svg></div>
                <div>
                    <h3>Finance &amp; procurement</h3>
                    <p>Income, expenses, suppliers, and purchase orders, farm by farm.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
