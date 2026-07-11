<?php use App\Core\Auth; use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($farm['name']) ?></h4>
        <p class="text-muted mb-0">
            <?= htmlspecialchars(trim(($farm['district'] ?? '') . ' / ' . ($farm['village'] ?? ''), ' /')) ?: 'No location set' ?>
            &middot; <?= $farm['size_hectares'] !== null ? htmlspecialchars($farm['size_hectares']) . ' ha' : 'size unknown' ?>
            &middot; Owner: <?= htmlspecialchars($farm['owner_name'] ?? 'unassigned') ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::hasPermission('farms.edit')): ?>
        <a href="/farms/<?= (int) $farm['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">Edit Farm</a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('farms.delete')): ?>
        <form method="post" action="/farms/<?= (int) $farm['id'] ?>/delete" onsubmit="return confirm('Delete this farm and all its blocks/plots?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete Farm</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
$plotCount = 0;
$totalPlotHectares = 0.0;
foreach ($blocks as $b) {
    $plotCount += count($b['plots']);
    foreach ($b['plots'] as $p) {
        $totalPlotHectares += (float) ($p['size_hectares'] ?? 0);
    }
}
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small"><i class="bi bi-grid-3x3-gap me-1"></i>Blocks</div>
            <div class="fs-3 fw-bold"><?= count($blocks) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small"><i class="bi bi-bounding-box me-1"></i>Plots</div>
            <div class="fs-3 fw-bold"><?= $plotCount ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small"><i class="bi bi-arrows-angle-expand me-1"></i>Farm Size</div>
            <div class="fs-3 fw-bold"><?= $farm['size_hectares'] !== null ? htmlspecialchars($farm['size_hectares']) : '—' ?> <span class="fs-6 fw-normal text-muted">ha</span></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small"><i class="bi bi-check2-circle me-1"></i>Plotted Area</div>
            <div class="fs-3 fw-bold"><?= number_format($totalPlotHectares, 1) ?> <span class="fs-6 fw-normal text-muted">ha</span></div>
        </div>
    </div>
</div>

<?php if ($farm['gps_lat'] && $farm['gps_lng']): ?>
<div class="card mb-4">
    <div class="card-body p-2">
        <div id="farmShowMap" style="height: 280px; border-radius: 0.5rem;"></div>
    </div>
</div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const map = L.map('farmShowMap').setView([<?= (float) $farm['gps_lat'] ?>, <?= (float) $farm['gps_lng'] ?>], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    L.marker([<?= (float) $farm['gps_lat'] ?>, <?= (float) $farm['gps_lng'] ?>]).addTo(map).bindPopup(<?= json_encode($farm['name']) ?>);
})();
</script>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Blocks &amp; Plots</h5>
    <?php if (Auth::hasPermission('farms.edit')): ?>
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addBlockModal">
        <i class="bi bi-plus-lg"></i> Add Block
    </button>
    <?php endif; ?>
</div>

<?php if (empty($blocks)): ?>
<div class="card"><div class="card-body text-center text-muted py-4">No blocks yet.</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($blocks as $block): ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-0"><?= htmlspecialchars($block['name']) ?></h6>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($block['description'] ?? '') ?></p>
                    </div>
                    <?php if (Auth::hasPermission('farms.edit')): ?>
                    <form method="post" action="/blocks/<?= (int) $block['id'] ?>/delete" onsubmit="return confirm('Delete this block and its plots?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>

                <table class="table table-sm mb-2">
                    <thead><tr><th>Plot</th><th>Size (ha)</th><th>Crop</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($block['plots'])): ?>
                        <tr><td colspan="4" class="text-muted small">No plots yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($block['plots'] as $plot): ?>
                        <tr>
                            <td><?= htmlspecialchars($plot['plot_code']) ?></td>
                            <td><?= $plot['size_hectares'] !== null ? htmlspecialchars($plot['size_hectares']) : '—' ?></td>
                            <td><?= htmlspecialchars($plot['current_crop_type'] ?? '—') ?></td>
                            <td class="text-end">
                                <?php if (Auth::hasPermission('farms.edit')): ?>
                                <form method="post" action="/plots/<?= (int) $plot['id'] ?>/delete" onsubmit="return confirm('Delete this plot?');">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (Auth::hasPermission('farms.edit')): ?>
                <form method="post" action="/blocks/<?= (int) $block['id'] ?>/plots" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-4"><input type="text" name="plot_code" class="form-control form-control-sm" placeholder="Plot code" required></div>
                    <div class="col-3"><input type="text" name="size_hectares" class="form-control form-control-sm" placeholder="Size ha"></div>
                    <div class="col-3"><input type="text" name="current_crop_type" class="form-control form-control-sm" placeholder="Crop"></div>
                    <div class="col-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                    <input type="hidden" name="gps_lat" value="">
                    <input type="hidden" name="gps_lng" value="">
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if (Auth::hasPermission('farms.edit')): ?>
<div class="modal fade" id="addBlockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/farms/<?= (int) $farm['id'] ?>/blocks">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add Block</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Block name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Add Block</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
