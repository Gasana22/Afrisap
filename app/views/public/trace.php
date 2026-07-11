<?php $statusBadge = ['active' => 'success', 'completed' => 'secondary', 'recalled' => 'danger'][$batch['status']] ?? 'secondary'; ?>

<?php if ($batch['status'] === 'recalled'): ?>
<div class="alert alert-danger text-center fw-bold">
    <i class="bi bi-exclamation-triangle"></i> This batch has been recalled. Please contact the seller.
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body text-center py-4">
        <div class="text-muted text-uppercase small mb-1">Verified Product</div>
        <h2 class="mb-1"><?= htmlspecialchars($origin['product_name'] ?? 'Unknown Product') ?></h2>
        <div class="font-monospace text-muted mb-2"><?= htmlspecialchars($batch['batch_code']) ?></div>
        <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= htmlspecialchars($batch['status']) ?></span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="text-muted small">Farm</div>
            <div class="fw-bold"><?= htmlspecialchars($origin['farm_name'] ?? '—') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="text-muted small">Origin</div>
            <div class="fw-bold"><?= htmlspecialchars(trim(($origin['district'] ?? '') . ' / ' . ($origin['village'] ?? ''), ' /')) ?: '—' ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="text-muted small">Production Date</div>
            <div class="fw-bold"><?= htmlspecialchars($origin['production_date'] ?? '—') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="text-muted small">Harvest Date</div>
            <div class="fw-bold"><?= htmlspecialchars($origin['harvest_date'] ?? '—') ?></div>
        </div>
    </div>
</div>

<?php if ($origin['gps_lat'] ?? null): ?>
<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-2"><i class="bi bi-geo-alt text-success"></i> GPS Origin</h6>
        <p class="mb-0 font-monospace"><?= htmlspecialchars($origin['gps_lat'] . ', ' . $origin['gps_lng']) ?></p>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-patch-check text-success"></i> Certification</h6>
                <?php if (empty($approvedCertifications) && empty($certificates)): ?>
                <p class="text-muted small mb-0">No certifications on record for this batch.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($approvedCertifications as $type): ?>
                    <li class="mb-1"><span class="badge bg-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($type) ?></span></li>
                    <?php endforeach; ?>
                    <?php foreach ($certificates as $cert): ?>
                    <li class="mb-1"><a href="/<?= htmlspecialchars($cert['file_path']) ?>" target="_blank">View Certificate Document</a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($origin['workers'])): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-2"><i class="bi bi-people text-success"></i> Worker History</h6>
                <p class="mb-0 small"><?= htmlspecialchars($origin['workers']) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-box-seam text-success"></i> Processing History</h6>
                <?php if (empty($journeyStages)): ?>
                <p class="text-muted small mb-0">No post-harvest processing recorded yet.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach (array_reverse($journeyStages) as $stage): ?>
                    <li class="mb-2">
                        <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($stage['stage']) ?></span>
                        <span class="small text-muted"><?= htmlspecialchars($stage['stage_date']) ?><?= $stage['location'] ? ' — ' . htmlspecialchars($stage['location']) : '' ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="mb-3"><i class="bi bi-clock-history text-success"></i> Full Journey Timeline</h6>
        <?php if (empty($timeline)): ?>
        <p class="text-muted small mb-0">No events recorded yet.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($timeline as $event): ?>
            <li class="list-group-item px-0">
                <div class="d-flex justify-content-between">
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($event['category']) ?></span>
                    <span class="text-muted small"><?= htmlspecialchars($event['date']) ?></span>
                </div>
                <div class="small mt-1"><?= htmlspecialchars($event['description']) ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
