<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

if (is_post() && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'regenerate') {
        require_once ROOT_PATH . '/includes/qrcode.php';

        $qrId = clean_int($_POST['qr_id'] ?? 0);
        $qrRow = db_one(
            'SELECT tqc.* FROM trace_qr_codes tqc JOIN trace_batches tb ON tb.id = tqc.batch_id WHERE tqc.id = :id AND tb.organization_id = :org',
            ['id' => $qrId, 'org' => $orgId]
        );

        if ($qrRow) {
            $batch = tenant_find('trace_batches', $orgId, (int) $qrRow['batch_id']);
            if ($batch) {
                $oldValues = $qrRow;
                db_delete('trace_qr_codes', 'id = :id', ['id' => $qrId]);

                $url = batch_trace_url($organization, $batch['batch_id']);
                $relativePath = generate_qr_code_file($url, $orgId, $batch['batch_id']);
                $newId = db_insert('trace_qr_codes', [
                    'batch_id' => $batch['id'],
                    'qr_code' => $relativePath,
                ]);

                audit_log($orgId, $currentUser['id'], 'update', 'trace_qr_codes', $newId, $oldValues, ['batch_id' => $batch['id'], 'qr_code' => $relativePath]);
                session_flash('success', 'QR code regenerated for ' . $batch['batch_id'] . '.');
            }
        } else {
            session_flash('error', 'QR code not found.');
        }

        redirect('org-admin/traceability/qr-codes.php');
    }
}

$qrCodes = db_all(
    'SELECT tqc.*, tb.batch_id AS batch_code, tb.product_type, tb.status AS batch_status
     FROM trace_qr_codes tqc
     JOIN trace_batches tb ON tb.id = tqc.batch_id
     WHERE tb.organization_id = :org
     ORDER BY tqc.generated_at DESC',
    ['org' => $orgId]
);

render_header(['title' => 'QR Codes', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/traceability/batches.php') ?>">Traceability</a></li>
        <li class="breadcrumb-item active">QR Codes</li>
      </ol></nav>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Generated QR Codes</h4>
          <p class="text-muted small mb-0">All chain-of-custody QR codes generated across your batches.</p>
        </div>
      </div>

      <div class="content-card">
        <?php if (!$qrCodes): ?>
          <?php render_empty_state('No QR codes generated yet. Open a batch and generate one.', 'bi-qr-code'); ?>
        <?php else: ?>
          <table class="table-app align-middle">
            <thead>
              <tr>
                <th>QR</th>
                <th>Batch ID</th>
                <th>Product</th>
                <th>Scans</th>
                <th>Last Scanned</th>
                <th>Generated</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($qrCodes as $qr): ?>
                <tr>
                  <td><img src="<?= e(uploaded_file_url($qr['qr_code'])) ?>" alt="QR" style="width:48px;height:48px;"></td>
                  <td><code><?= e($qr['batch_code']) ?></code></td>
                  <td><?= e($qr['product_type']) ?></td>
                  <td><?= (int) ($qr['scans_count'] ?? 0) ?></td>
                  <td><?= $qr['last_scanned_at'] ? format_date($qr['last_scanned_at'], 'd M Y H:i') : 'Never' ?></td>
                  <td><?= format_date($qr['generated_at'], 'd M Y H:i') ?></td>
                  <td class="text-end">
                    <a href="<?= base_url('org-admin/traceability/view-batch.php?id=' . $qr['batch_id']) ?>" class="btn btn-sm btn-outline-primary">View Batch</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Regenerate QR code for this batch? The old image will be replaced.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="regenerate">
                      <input type="hidden" name="qr_id" value="<?= $qr['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
