<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$modules = [
    [
        'icon' => 'bi-flower1',
        'title' => 'Crop Management',
        'text' => 'Plan seasonal crop cycles across farms, blocks and plots. Track planting, irrigation, spraying,
            weeding, fertilizing and harvest activities with cost and worker attribution, and monitor expected
            versus actual yield for every cycle.',
    ],
    [
        'icon' => 'bi-piggy-bank',
        'title' => 'Livestock Tracking',
        'text' => 'Register and tag every animal, log health events and vaccinations, track breeding records and
            weight history, and keep a complete lifecycle record for herds of any size.',
    ],
    [
        'icon' => 'bi-people',
        'title' => 'Worker Management',
        'text' => 'Maintain worker profiles and employee IDs, assign daily tasks, record attendance in the field,
            and manage payroll &mdash; all tied back to the farms and activities each worker touches.',
    ],
    [
        'icon' => 'bi-box-seam',
        'title' => 'Inventory &amp; Procurement',
        'text' => 'Track stock levels for seeds, fertilizers, tools and produce, manage a supplier directory with
            ratings and payment terms, and raise purchase orders that automatically update inventory on receipt.',
    ],
    [
        'icon' => 'bi-cash-coin',
        'title' => 'Financial Reporting',
        'text' => 'Log income and expenses per farm and fiscal year, set budgets by category, and generate
            financial reports and exports (PDF, Excel, CSV) for season-end reviews and investor updates.',
    ],
    [
        'icon' => 'bi-qr-code',
        'title' => 'QR Traceability',
        'text' => 'Assign every production batch a unique batch ID and QR code. Consumers scan the code to see the
            full chain of custody &mdash; origin farm, production date, processing stages and every recorded event
            &mdash; building trust in your product.',
    ],
    [
        'icon' => 'bi-truck',
        'title' => 'Supply Chain Events',
        'text' => 'Record every handoff a batch goes through &mdash; harvest, storage, processing, transport,
            delivery &mdash; with timestamps, GPS location and photos, so nothing about a product\'s journey is a
            mystery.',
    ],
    [
        'icon' => 'bi-graph-up',
        'title' => 'Dashboards &amp; Reporting',
        'text' => 'Role-aware dashboards surface the metrics that matter for owners, managers, agronomists and
            accountants, with drill-down reports across every module.',
    ],
    [
        'icon' => 'bi-diagram-3',
        'title' => 'Multi-Tenant &amp; Multi-Farm',
        'text' => 'Manage multiple farms under one organization, each with its own blocks, plots and staff, while
            keeping every organization\'s data fully isolated and secure.',
    ],
];

render_header(['title' => 'Features', 'context' => 'public']);
render_public_navbar();
?>
<section class="hero-section py-5">
  <div class="container text-center">
    <h1 class="fw-bold">Every module your farm business needs</h1>
    <p class="lead">One login, one dashboard, one source of truth &mdash; from the field to the ledger.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($modules as $m): ?>
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon"><i class="bi <?= e($m['icon']) ?>"></i></div>
            <h5 class="fw-semibold"><?= $m['title'] ?></h5>
            <p class="text-muted mb-0"><?= $m['text'] ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container text-center">
    <h2 class="fw-bold mb-3">See it in action</h2>
    <p class="text-muted mb-4">Request a personalized walkthrough or start a free 14-day trial today.</p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="<?= base_url('public/demo.php') ?>" class="btn btn-outline-primary btn-lg">Request a Demo</a>
      <a href="<?= base_url('public/register.php') ?>" class="btn btn-primary btn-lg">Start Free Trial</a>
    </div>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
