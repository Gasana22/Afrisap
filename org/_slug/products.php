<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$slug = clean_string($_GET['slug'] ?? '');
$organization = require_tenant_slug($slug);

$batches = db_all(
    "SELECT * FROM trace_batches WHERE organization_id = :org_id AND status IN ('active','completed')
     ORDER BY product_type ASC, production_date DESC",
    ['org_id' => $organization['id']]
);

$grouped = [];
foreach ($batches as $batch) {
    $grouped[$batch['product_type']][] = $batch;
}

render_header(['title' => 'Products | ' . $organization['name'], 'context' => 'public']);
?>
<header class="py-4 border-bottom bg-white">
  <div class="container d-flex align-items-center gap-3">
    <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/index.php') ?>" class="text-decoration-none">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-bold"><?= e($organization['name']) ?> &middot; Products</h4>
  </div>
</header>

<section class="py-5">
  <div class="container">
    <?php if (!$grouped): ?>
      <?php render_empty_state('This farm has no publicly traceable products yet.', 'bi-box-seam'); ?>
    <?php else: ?>
      <?php foreach ($grouped as $productType => $items): ?>
        <h5 class="fw-semibold mb-3"><?= e(humanize($productType)) ?></h5>
        <div class="row g-4 mb-5">
          <?php foreach ($items as $batch): ?>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="feature-icon"><i class="bi bi-box-seam"></i></div>
                  <?php render_status_badge($batch['status']); ?>
                </div>
                <h5 class="fw-semibold"><?= e(humanize($batch['product_type'])) ?></h5>
                <p class="text-muted small mb-1"><i class="bi bi-upc-scan me-1"></i>Batch: <?= e($batch['batch_id']) ?></p>
                <p class="text-muted small mb-1"><i class="bi bi-calendar3 me-1"></i>Produced: <?= e(format_date($batch['production_date'])) ?></p>
                <?php if ($batch['quantity']): ?>
                  <p class="text-muted small mb-3"><i class="bi bi-basket me-1"></i><?= e(rtrim(rtrim(number_format((float) $batch['quantity'], 2), '0'), '.')) ?> <?= e($batch['unit'] ?? '') ?></p>
                <?php endif; ?>
                <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/product.php?batch=' . urlencode($batch['batch_id'])) ?>" class="btn btn-outline-primary w-100">View Journey</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
