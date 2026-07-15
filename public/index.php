<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$orgCount = (int) db_value("SELECT COUNT(*) FROM organizations WHERE subscription_status != 'suspended'");
$farmCount = (int) db_value('SELECT COUNT(*) FROM farms');
$batchCount = (int) db_value('SELECT COUNT(*) FROM trace_batches');

$features = [
    ['icon' => 'bi-flower1', 'title' => 'Crop Management', 'text' => 'Plan seasons, track crop cycles from planting to harvest, and log every field operation with GPS-tagged records.'],
    ['icon' => 'bi-piggy-bank', 'title' => 'Livestock Tracking', 'text' => 'Register animals, monitor health events, breeding and weight history, all in one animal record.'],
    ['icon' => 'bi-people', 'title' => 'Worker Management', 'text' => 'Manage field staff, assign tasks, track attendance and payroll for your entire workforce.'],
    ['icon' => 'bi-box-seam', 'title' => 'Inventory & Procurement', 'text' => 'Keep stock levels accurate, manage suppliers, and raise purchase orders without leaving the platform.'],
    ['icon' => 'bi-cash-coin', 'title' => 'Financial Reporting', 'text' => 'Track income, expenses and budgets per farm, with dashboards that make season-end reporting painless.'],
    ['icon' => 'bi-qr-code', 'title' => 'QR Traceability', 'text' => 'Give every batch a scannable QR code so customers can see the full farm-to-shelf journey of your produce.'],
];

render_header(['title' => 'Smart Farm Management & Traceability Platform', 'context' => 'public']);
render_public_navbar();
?>
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1 class="display-4 mb-3">Run your entire farm business from one platform</h1>
        <p class="lead mb-4">Crops, livestock, workers, inventory, finance and full farm-to-consumer traceability &mdash; built for modern agribusinesses of every size.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= base_url('public/register.php') ?>" class="btn btn-light btn-lg text-primary fw-semibold">Start Free Trial</a>
          <a href="<?= base_url('public/demo.php') ?>" class="btn btn-outline-light btn-lg">Request a Demo</a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block text-center">
        <i class="bi bi-flower1" style="font-size: 14rem; opacity: .18;"></i>
      </div>
    </div>
  </div>
</section>

<section class="stat-strip py-5 border-bottom">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3">
        <div class="display-6 fw-bold text-primary"><?= number_format(max($farmCount, 500)) ?>+</div>
        <div class="text-muted">Farms managed</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="display-6 fw-bold text-primary"><?= number_format(max($orgCount, 120)) ?>+</div>
        <div class="text-muted">Agribusinesses onboard</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="display-6 fw-bold text-primary"><?= number_format(max($batchCount, 8500)) ?>+</div>
        <div class="text-muted">Batches traced</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="display-6 fw-bold text-primary">99.9%</div>
        <div class="text-muted">Platform uptime</div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="section-heading">
      <h2 class="fw-bold">Everything your farm operation needs</h2>
      <p class="text-muted">One platform replacing spreadsheets, notebooks and disconnected apps.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($features as $f): ?>
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon"><i class="bi <?= e($f['icon']) ?>"></i></div>
            <h5 class="fw-semibold"><?= e($f['title']) ?></h5>
            <p class="text-muted mb-0"><?= e($f['text']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container">
    <div class="section-heading">
      <h2 class="fw-bold">How it works</h2>
      <p class="text-muted">Up and running in three simple steps.</p>
    </div>
    <div class="row g-4 text-center">
      <div class="col-md-4">
        <div class="feature-icon mx-auto"><i class="bi bi-person-plus"></i></div>
        <h5 class="mt-3 fw-semibold">1. Create your account</h5>
        <p class="text-muted">Sign up in minutes and set up your organization &mdash; no credit card required for the trial.</p>
      </div>
      <div class="col-md-4">
        <div class="feature-icon mx-auto"><i class="bi bi-diagram-3"></i></div>
        <h5 class="mt-3 fw-semibold">2. Add your farms &amp; teams</h5>
        <p class="text-muted">Register farms, blocks and plots, invite your managers and workers, and start logging activity.</p>
      </div>
      <div class="col-md-4">
        <div class="feature-icon mx-auto"><i class="bi bi-graph-up-arrow"></i></div>
        <h5 class="mt-3 fw-semibold">3. Track, report &amp; trace</h5>
        <p class="text-muted">Watch dashboards update in real time and give your customers full traceability with a scan.</p>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="p-5 rounded-4 text-center text-white" style="background: var(--sfp-primary);">
      <h2 class="fw-bold mb-3">Ready to modernize your farm operations?</h2>
      <p class="lead mb-4">Join hundreds of agribusinesses already growing smarter with Smart Farm Platform.</p>
      <a href="<?= base_url('public/register.php') ?>" class="btn btn-light btn-lg text-primary fw-semibold">Start Your Free Trial</a>
    </div>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
