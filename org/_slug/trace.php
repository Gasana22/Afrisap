<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$slug = clean_string($_GET['slug'] ?? '');
$organization = require_tenant_slug($slug);

$batchCode = clean_string($_GET['batch'] ?? '');

$batch = $batchCode ? db_one(
    'SELECT tb.*, f.name AS farm_name, f.district AS farm_district, f.village AS farm_village,
            bl.name AS block_name, pl.name AS plot_name
     FROM trace_batches tb
     LEFT JOIN farms f ON f.id = tb.farm_id
     LEFT JOIN blocks bl ON bl.id = tb.block_id
     LEFT JOIN plots pl ON pl.id = tb.plot_id
     WHERE tb.batch_id = :batch_id AND tb.organization_id = :org_id',
    ['batch_id' => $batchCode, 'org_id' => $organization['id']]
) : null;

$journey = [];
$events = [];

if ($batch) {
    $journey = db_all(
        'SELECT * FROM product_journey WHERE batch_id = :batch_id ORDER BY stage_order ASC, start_date ASC',
        ['batch_id' => $batch['id']]
    );

    $events = db_all(
        'SELECT * FROM trace_events WHERE batch_id = :batch_id ORDER BY event_date ASC',
        ['batch_id' => $batch['id']]
    );

    // Track the scan: this page is what a QR code (batch_trace_url()) points to.
    db_query(
        'UPDATE trace_qr_codes SET scans_count = scans_count + 1, last_scanned_at = NOW() WHERE batch_id = :batch_id',
        ['batch_id' => $batch['id']]
    );
}

render_header(['title' => ($batch ? humanize($batch['product_type']) . ' - ' : '') . 'Traceability | ' . $organization['name'], 'context' => 'public']);
?>
<header class="py-4 border-bottom bg-white">
  <div class="container d-flex align-items-center gap-3">
    <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/index.php') ?>" class="text-decoration-none">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-bold"><?= e($organization['name']) ?> &middot; Traceability Record</h4>
  </div>
</header>

<section class="py-5">
  <div class="container" style="max-width: 860px;">
    <?php if (!$batch): ?>
      <?php render_empty_state('We could not find a traceability record for that batch code.', 'bi-shield-x'); ?>
      <div class="text-center">
        <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/qr.php') ?>" class="btn btn-outline-primary">Try Another Code</a>
      </div>
    <?php else: ?>
      <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-patch-check-fill fs-4"></i>
        <div><strong>Verified by <?= e($organization['name']) ?></strong> &middot; this is an authentic traceability record.</div>
      </div>

      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 my-4">
        <div>
          <h2 class="fw-bold mb-1"><?= e(humanize($batch['product_type'])) ?></h2>
          <p class="text-muted mb-0">Batch <?= e($batch['batch_id']) ?></p>
        </div>
        <?php render_status_badge($batch['status']); ?>
      </div>

      <h5 class="fw-semibold mb-3">Origin</h5>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="feature-card text-center h-100">
            <div class="text-muted small">Farm</div>
            <div class="fw-semibold"><?= e($batch['farm_name'] ?? 'Not specified') ?></div>
            <?php if (!empty($batch['farm_district']) || !empty($batch['farm_village'])): ?>
              <div class="text-muted small"><?= e(trim(($batch['farm_village'] ?? '') . ', ' . ($batch['farm_district'] ?? ''), ', ')) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card text-center h-100">
            <div class="text-muted small">Block</div>
            <div class="fw-semibold"><?= e($batch['block_name'] ?? 'Not specified') ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card text-center h-100">
            <div class="text-muted small">Plot</div>
            <div class="fw-semibold"><?= e($batch['plot_name'] ?? 'Not specified') ?></div>
          </div>
        </div>
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
        <p class="text-muted mb-5">No journey stages have been recorded for this batch yet.</p>
      <?php else: ?>
        <div class="trace-timeline mb-5">
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

      <h5 class="fw-semibold mb-3">Chain of Custody Events</h5>
      <?php if (!$events): ?>
        <p class="text-muted mb-0">No traceability events have been recorded for this batch yet.</p>
      <?php else: ?>
        <div class="trace-timeline">
          <?php foreach ($events as $event): ?>
            <div class="trace-step">
              <h6 class="fw-semibold mb-1"><?= e(humanize($event['event_type'])) ?></h6>
              <p class="text-muted small mb-1">
                <?= e(format_date($event['event_date'], 'd M Y H:i')) ?>
                <?php if (!empty($event['location'])): ?>
                  &middot; <i class="bi bi-geo-alt"></i> <?= e($event['location']) ?>
                <?php endif; ?>
              </p>
              <?php if (!empty($event['description'])): ?>
                <p class="mb-0"><?= e($event['description']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
