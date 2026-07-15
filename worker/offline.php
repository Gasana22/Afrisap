<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Lightweight on purpose: this page should still render (from browser/HTTP
// cache) when the device has no connectivity at all, so it avoids any
// non-essential DB queries beyond the auth check itself.
$worker = require_worker();

render_header(['title' => 'Offline', 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content text-center">
    <div class="content-card p-4 mt-3">
        <i class="bi bi-wifi-off display-3 text-muted d-block mb-3"></i>
        <h5 class="fw-bold">You appear to be offline</h5>
        <p class="text-muted">
            No internet connection was detected. You can keep using the worker portal -
            clock in/out, log activities, and update tasks will be saved on this device and
            sent to the server automatically as soon as your connection comes back.
        </p>
        <p class="mb-1">Actions waiting to sync:</p>
        <span id="offlinePendingCount" class="badge bg-warning fs-6"></span>
        <p class="small text-muted mt-3 mb-0">This page will refresh automatically once your connection is restored.</p>
    </div>
    <a href="<?= base_url('worker/index.php') ?>" class="btn btn-primary w-100 mt-3"><i class="bi bi-house me-1"></i> Back to Dashboard</a>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker', 'js' => ['offline']]); ?>
<script>
window.addEventListener('online', () => window.location.reload());
</script>
