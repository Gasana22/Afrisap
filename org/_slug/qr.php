<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$slug = clean_string($_GET['slug'] ?? '');
$organization = require_tenant_slug($slug);

$traceBase = base_url('org/' . urlencode($organization['slug']) . '/trace.php');

render_header(['title' => 'Scan a Product | ' . $organization['name'], 'context' => 'public']);
?>
<header class="py-4 border-bottom bg-white">
  <div class="container d-flex align-items-center gap-3">
    <a href="<?= base_url('org/' . urlencode($organization['slug']) . '/index.php') ?>" class="text-decoration-none">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-bold"><?= e($organization['name']) ?> &middot; Verify a Product</h4>
  </div>
</header>

<section class="py-5">
  <div class="container" style="max-width: 560px;">
    <div class="feature-card text-center mb-4">
      <div class="feature-icon mx-auto"><i class="bi bi-qr-code-scan"></i></div>
      <h5 class="fw-semibold mt-2">Enter a Batch Code</h5>
      <p class="text-muted">Type the batch code printed near the QR code on your product's label.</p>

      <form id="batchLookupForm" method="get" action="<?= e($traceBase) ?>">
        <div class="input-group mb-2">
          <input type="text" id="batchCodeInput" name="batch" class="form-control" placeholder="e.g. TRC-DEMO0001" required>
          <button type="submit" class="btn btn-primary">Look Up</button>
        </div>
      </form>
    </div>

    <div class="feature-card text-center">
      <h5 class="fw-semibold mb-2">Or Scan With Your Camera</h5>
      <p class="text-muted small">Works best on a mobile device with a rear camera.</p>

      <div id="qrScannerArea" class="d-none">
        <video id="qrVideo" class="w-100 rounded-3 mb-2" style="max-height:320px;background:#000;" muted playsinline></video>
        <canvas id="qrCanvas" class="d-none"></canvas>
        <p id="qrStatus" class="text-muted small"></p>
      </div>

      <button type="button" id="startScanBtn" class="btn btn-outline-primary">
        <i class="bi bi-camera"></i> Scan with Camera
      </button>
    </div>
  </div>
</section>

<script src="<?= asset_url('js/qr-scanner.js') ?>"></script>
<script>
document.getElementById('startScanBtn').addEventListener('click', function () {
    document.getElementById('qrScannerArea').classList.remove('d-none');
    this.classList.add('d-none');
    initQrScanner({
        videoId: 'qrVideo',
        canvasId: 'qrCanvas',
        resultInputId: 'batchCodeInput',
        formId: 'batchLookupForm',
        statusId: 'qrStatus',
    });
});
</script>
<?php render_footer(['context' => 'public']); ?>
