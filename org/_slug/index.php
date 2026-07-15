<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$slug = clean_string($_GET['slug'] ?? '');
$organization = require_tenant_slug($slug);

$settings = json_decode($organization['settings'] ?? '', true) ?: [];
$description = $settings['description']
    ?? 'A proud member of the Smart Farm Platform network, committed to responsible farming and full traceability from field to customer.';

$logoUrl = uploaded_file_url($organization['logo'] ?? null);

render_header(['title' => e($organization['name']), 'context' => 'public']);
?>
<header class="py-4 border-bottom bg-white">
  <div class="container d-flex align-items-center gap-3">
    <?php if ($logoUrl): ?>
      <img src="<?= e($logoUrl) ?>" alt="<?= e($organization['name']) ?>" style="height:56px;width:56px;object-fit:cover;border-radius:12px;">
    <?php else: ?>
      <div class="feature-icon"><i class="bi bi-flower1"></i></div>
    <?php endif; ?>
    <div>
      <h4 class="mb-0 fw-bold"><?= e($organization['name']) ?></h4>
      <span class="text-muted small">Verified on Smart Farm Platform</span>
    </div>
  </div>
</header>

<section class="hero-section py-5">
  <div class="container text-center">
    <h1 class="fw-bold">Welcome to <?= e($organization['name']) ?></h1>
    <p class="lead mb-4"><?= e($description) ?></p>
    <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/products.php') ?>" class="btn btn-light btn-lg text-primary fw-semibold">
      <i class="bi bi-box-seam"></i> View Our Products
    </a>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4 text-center">
      <div class="col-md-4">
        <div class="feature-card h-100">
          <div class="feature-icon mx-auto"><i class="bi bi-box-seam"></i></div>
          <h5 class="fw-semibold mt-2">Browse Products</h5>
          <p class="text-muted mb-0">See every traceable batch this farm has produced.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100">
          <div class="feature-icon mx-auto"><i class="bi bi-qr-code-scan"></i></div>
          <h5 class="fw-semibold mt-2">Scan a QR Code</h5>
          <p class="text-muted mb-0">Already have a product in hand? <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/qr.php') ?>">Scan or enter its code</a> to see its journey.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100">
          <div class="feature-icon mx-auto"><i class="bi bi-shield-check"></i></div>
          <h5 class="fw-semibold mt-2">Full Traceability</h5>
          <p class="text-muted mb-0">Every batch is tracked from origin farm to your table.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
