<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$slug = clean_string($_GET['slug'] ?? '');
$organization = require_tenant_slug($slug);

$batchCode = clean_string($_GET['batch'] ?? '');

$batch = $batchCode ? db_one(
    'SELECT * FROM trace_batches WHERE batch_id = :batch_id AND organization_id = :org_id',
    ['batch_id' => $batchCode, 'org_id' => $organization['id']]
) : null;

$journey = [];
if ($batch) {
    $journey = db_all(
        'SELECT * FROM product_journey WHERE batch_id = :batch_id ORDER BY stage_order ASC, start_date ASC',
        ['batch_id' => $batch['id']]
    );
}

render_header(['title' => ($batch ? humanize($batch['product_type']) . ' - ' : '') . 'Product Journey | ' . $organization['name'], 'context' => 'public']);
?>
<header class="py-4 border-bottom bg-white">
  <div class="container d-flex align-items-center gap-3">
    <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/products.php') ?>" class="text-decoration-none">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-bold"><?= e($organization['name']) ?> &middot; Product Journey</h4>
  </div>
</header>

<section class="py-5">
  <div class="container" style="max-width: 820px;">
    <?php if (!$batch): ?>
      <?php render_empty_state('We could not find that product. The batch code may be incorrect.', 'bi-box-seam'); ?>
      <div class="text-center">
        <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/products.php') ?>" class="btn btn-outline-primary">Back to Products</a>
      </div>
    <?php else: ?>
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
          <h2 class="fw-bold mb-1"><?= e(humanize($batch['product_type'])) ?></h2>
          <p class="text-muted mb-0">Batch <?= e($batch['batch_id']) ?></p>
        </div>
        <?php render_status_badge($batch['status']); ?>
      </div>

      <div class="row g-3 mb-5">
        <div class="col-md-4">
          <div class="feature-card text-center h-100">
            <div class="text-muted small">Production Date</div>
            <div class="fw-semibold"><?= e(format_date($batch['production_date'])) ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card text-center h-100">
            <div class="text-muted small">Quantity</div>
            <div class="fw-semibold"><?= $batch['quantity'] !== null ? e(rtrim(rtrim(number_format((float) $batch['quantity'], 2), '0'), '.')) . ' ' . e($batch['unit'] ?? '') : '-' ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card text-center h-100">
            <div class="text-muted small">Current Location</div>
            <div class="fw-semibold"><?= e($batch['current_location'] ?? '-') ?></div>
          </div>
        </div>
      </div>

      <h5 class="fw-semibold mb-3">Product Journey</h5>
      <?php if (!$journey): ?>
        <?php render_empty_state('No journey stages have been recorded for this batch yet.', 'bi-signpost'); ?>
      <?php else: ?>
        <?php render_journey_progress($journey); ?>
        <h6 class="fw-semibold text-muted text-uppercase small mb-3 mt-4">Journey Details</h6>
        <div class="trace-timeline mb-4">
          <?php foreach ($journey as $stage): ?>
            <div class="trace-step">
              <h6 class="fw-semibold mb-1"><?= e(humanize($stage['stage'])) ?></h6>
              <p class="text-muted small mb-1">
                <?= e(format_date($stage['start_date'])) ?><?= $stage['end_date'] ? ' &ndash; ' . e(format_date($stage['end_date'])) : '' ?>
                <?php if (!empty($stage['responsible_party'])): ?>
                  &middot; <?= e($stage['responsible_party']) ?>
                <?php endif; ?>
              </p>
              <?php if (!empty($stage['notes'])): ?>
                <p class="mb-0"><?= e($stage['notes']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="text-center mt-5">
        <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/trace.php?batch=' . urlencode($batch['batch_id'])) ?>" class="btn btn-primary">
          <i class="bi bi-shield-check"></i> View Full Traceability Record
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
