<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$batchId = (int) clean_int($_GET['id'] ?? 0);
$batch = tenant_find('trace_batches', $orgId, $batchId);

if (!$batch) {
    session_flash('error', 'Trace batch not found.');
    redirect('org-admin/traceability/batches.php');
}

$errors = [];

if (is_post() && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_journey') {
        $stage = clean_string($_POST['stage'] ?? '');
        if ($stage === '') {
            session_flash('error', 'Stage name is required.');
        } else {
            $maxOrder = (int) db_value('SELECT COALESCE(MAX(stage_order), 0) FROM product_journey WHERE batch_id = :batch_id', ['batch_id' => $batchId]);
            db_insert('product_journey', [
                'batch_id' => $batchId,
                'stage' => $stage,
                'stage_order' => $maxOrder + 1,
                'start_date' => ($_POST['start_date'] ?? '') !== '' ? $_POST['start_date'] : null,
                'responsible_party' => clean_string($_POST['responsible_party'] ?? ''),
                'notes' => clean_string($_POST['notes'] ?? ''),
            ]);
            session_flash('success', 'Journey stage added.');
        }
        redirect('org-admin/traceability/view-batch.php?id=' . $batchId);
    }

    if ($action === 'add_event') {
        $eventType = clean_string($_POST['event_type'] ?? '');
        if ($eventType === '') {
            session_flash('error', 'Event type is required.');
        } else {
            db_insert('trace_events', [
                'batch_id' => $batchId,
                'event_type' => $eventType,
                'location' => clean_string($_POST['location'] ?? ''),
                'gps_latitude' => clean_float($_POST['gps_latitude'] ?? null),
                'gps_longitude' => clean_float($_POST['gps_longitude'] ?? null),
                'description' => clean_string($_POST['description'] ?? ''),
            ]);
            session_flash('success', 'Trace event recorded.');
        }
        redirect('org-admin/traceability/view-batch.php?id=' . $batchId);
    }

    if ($action === 'generate_qr') {
        require_once ROOT_PATH . '/includes/qrcode.php';

        $existing = db_one('SELECT * FROM trace_qr_codes WHERE batch_id = :batch_id', ['batch_id' => $batchId]);
        if (!$existing) {
            $url = batch_trace_url($organization, $batch['batch_id']);
            $relativePath = generate_qr_code_file($url, $orgId, $batch['batch_id']);
            $qrId = db_insert('trace_qr_codes', [
                'batch_id' => $batchId,
                'qr_code' => $relativePath,
            ]);
            audit_log($orgId, $currentUser['id'], 'create', 'trace_qr_codes', $qrId, null, ['batch_id' => $batchId, 'qr_code' => $relativePath]);
            session_flash('success', 'QR code generated.');
        }
        redirect('org-admin/traceability/view-batch.php?id=' . $batchId);
    }
}

$journey = db_all('SELECT * FROM product_journey WHERE batch_id = :batch_id ORDER BY stage_order ASC', ['batch_id' => $batchId]);
$events = db_all(
    'SELECT te.*, w.name AS actor_name FROM trace_events te LEFT JOIN workers w ON w.id = te.actor_id WHERE te.batch_id = :batch_id ORDER BY te.event_date DESC',
    ['batch_id' => $batchId]
);
$qrRow = db_one('SELECT * FROM trace_qr_codes WHERE batch_id = :batch_id', ['batch_id' => $batchId]);

require_once ROOT_PATH . '/includes/qrcode.php';
$traceUrl = batch_trace_url($organization, $batch['batch_id']);

render_header(['title' => 'Batch ' . $batch['batch_id'], 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/traceability/batches.php') ?>">Traceability</a></li>
        <li class="breadcrumb-item active"><?= e($batch['batch_id']) ?></li>
      </ol></nav>

      <div class="content-card mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <h4 class="mb-1"><?= e($batch['batch_id']) ?> <?php render_status_badge($batch['status']); ?></h4>
            <p class="text-muted mb-0"><?= e($batch['product_type']) ?></p>
          </div>
        </div>
        <hr>
        <div class="row g-3">
          <div class="col-md-3 col-6">
            <div class="text-muted small">Production Date</div>
            <div class="fw-semibold"><?= format_date($batch['production_date']) ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Quantity</div>
            <div class="fw-semibold"><?= $batch['quantity'] !== null ? number_format((float) $batch['quantity'], 2) . ' ' . e($batch['unit'] ?: '') : '-' ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Current Location</div>
            <div class="fw-semibold"><?= e($batch['current_location'] ?: '-') ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Created</div>
            <div class="fw-semibold"><?= format_date($batch['created_at']) ?></div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-7">
          <!-- Product Journey -->
          <div class="content-card mb-3">
            <h6 class="mb-3"><i class="bi bi-signpost-split"></i> Product Journey</h6>
            <?php if (!$journey): ?>
              <?php render_empty_state('No journey stages recorded yet.'); ?>
            <?php else: ?>
              <div class="journey-stepper">
                <?php foreach ($journey as $i => $stage): ?>
                  <div class="d-flex mb-3">
                    <div class="text-center me-3" style="width:32px;">
                      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><?= $i + 1 ?></div>
                      <?php if ($i < count($journey) - 1): ?><div style="border-left:2px solid #dee2e6;height:100%;margin:4px auto;width:0;"></div><?php endif; ?>
                    </div>
                    <div class="flex-fill pb-2">
                      <div class="fw-semibold"><?= e($stage['stage']) ?></div>
                      <div class="text-muted small">
                        <?= format_date($stage['start_date']) ?><?= $stage['end_date'] ? ' &rarr; ' . format_date($stage['end_date']) : '' ?>
                        <?= $stage['responsible_party'] ? ' &middot; ' . e($stage['responsible_party']) : '' ?>
                      </div>
                      <?php if ($stage['notes']): ?><div class="small mt-1"><?= e($stage['notes']) ?></div><?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <hr>
            <form method="post" class="row g-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add_journey">
              <div class="col-md-6">
                <input type="text" name="stage" class="form-control form-control-sm" placeholder="Stage name (e.g. Processing)" required>
              </div>
              <div class="col-md-3">
                <input type="date" name="start_date" class="form-control form-control-sm">
              </div>
              <div class="col-md-3">
                <input type="text" name="responsible_party" class="form-control form-control-sm" placeholder="Responsible party">
              </div>
              <div class="col-12">
                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Notes (optional)"></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-sm btn-primary">Add Stage</button>
              </div>
            </form>
          </div>

          <!-- Trace Events -->
          <div class="content-card">
            <h6 class="mb-3"><i class="bi bi-clock-history"></i> Trace Events</h6>
            <?php if (!$events): ?>
              <?php render_empty_state('No trace events recorded yet.'); ?>
            <?php else: ?>
              <ul class="list-unstyled">
                <?php foreach ($events as $ev): ?>
                  <li class="mb-3 pb-2 border-bottom">
                    <div class="d-flex justify-content-between">
                      <span class="fw-semibold text-capitalize"><?= e($ev['event_type']) ?></span>
                      <span class="text-muted small"><?= format_date($ev['event_date'], 'd M Y H:i') ?></span>
                    </div>
                    <div class="small text-muted">
                      <?= $ev['location'] ? '<i class="bi bi-geo-alt"></i> ' . e($ev['location']) : '' ?>
                      <?= $ev['actor_name'] ? ' &middot; ' . e($ev['actor_name']) : '' ?>
                    </div>
                    <?php if ($ev['description']): ?><div class="small mt-1"><?= e($ev['description']) ?></div><?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <hr>
            <form method="post" class="row g-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add_event">
              <div class="col-md-6">
                <input type="text" name="event_type" class="form-control form-control-sm" placeholder="e.g. planted, harvested, shipped" required>
              </div>
              <div class="col-md-6">
                <input type="text" name="location" class="form-control form-control-sm" placeholder="Location">
              </div>
              <div class="col-12">
                <input type="hidden" id="gps_latitude" name="gps_latitude">
                <input type="hidden" id="gps_longitude" name="gps_longitude">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-gps-capture data-lat-field="gps_latitude" data-lng-field="gps_longitude" data-status-field="gpsStatus"><i class="bi bi-geo-alt"></i> Capture GPS</button>
                <span id="gpsStatus" class="small text-muted ms-2"></span>
              </div>
              <div class="col-12">
                <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Description"></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-sm btn-primary">Add Event</button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-5">
          <!-- QR Code -->
          <div class="content-card">
            <h6 class="mb-3"><i class="bi bi-qr-code"></i> QR Code</h6>
            <?php if ($qrRow): ?>
              <div class="text-center mb-3">
                <img src="<?= e(uploaded_file_url($qrRow['qr_code'])) ?>" alt="QR code for <?= e($batch['batch_id']) ?>" class="img-fluid" style="max-width:220px;">
              </div>
              <div class="mb-2">
                <label class="form-label small">Public Trace URL</label>
                <div class="input-group input-group-sm">
                  <input type="text" id="traceUrlInput" class="form-control" value="<?= e($traceUrl) ?>" readonly>
                  <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('traceUrlInput').value)"><i class="bi bi-clipboard"></i> Copy</button>
                </div>
              </div>
              <div class="row text-center g-2 mt-1">
                <div class="col-6">
                  <div class="text-muted small">Scans</div>
                  <div class="fw-semibold"><?= (int) ($qrRow['scans_count'] ?? 0) ?></div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Last Scanned</div>
                  <div class="fw-semibold"><?= $qrRow['last_scanned_at'] ? format_date($qrRow['last_scanned_at'], 'd M Y H:i') : 'Never' ?></div>
                </div>
              </div>
              <div class="text-muted small mt-2">Generated <?= format_date($qrRow['generated_at'], 'd M Y H:i') ?></div>
            <?php else: ?>
              <?php render_empty_state('No QR code generated yet for this batch.', 'bi-qr-code'); ?>
              <form method="post" class="text-center">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="generate_qr">
                <button type="submit" class="btn btn-primary"><i class="bi bi-qr-code"></i> Generate QR Code</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="<?= asset_url('js/gps.js') ?>"></script>
<?php render_footer(['context' => 'org-admin']); ?>
