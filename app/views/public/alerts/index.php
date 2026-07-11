<h4 class="mb-4">Alerts</h4>

<?php if (empty($alerts)): ?>
<div class="card"><div class="card-body text-center text-muted py-5"><i class="bi bi-check-circle fs-1 text-success"></i><p class="mt-2 mb-0">No active alerts. Everything looks on track.</p></div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($alerts as $a):
        $icon = ['missing_records' => 'clock-history', 'expired_input' => 'calendar-x', 'disease_outbreak' => 'virus', 'inventory_variance' => 'box-seam', 'unverified_activity' => 'person-check'][$a['type']] ?? 'exclamation-triangle';
        $color = ['warning' => 'warning', 'danger' => 'danger'][$a['severity']] ?? 'secondary';
    ?>
    <div class="col-md-6">
        <div class="card border-<?= $color ?>">
            <div class="card-body d-flex gap-3">
                <div><i class="bi bi-<?= $icon ?> fs-3 text-<?= $color ?>"></i></div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <strong><?= htmlspecialchars($a['title']) ?></strong>
                        <span class="badge bg-<?= $color ?> text-capitalize"><?= htmlspecialchars($a['severity']) ?></span>
                    </div>
                    <p class="small text-muted mb-1"><?= htmlspecialchars($a['description']) ?></p>
                    <a href="<?= htmlspecialchars($a['link']) ?>" class="small">View details</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
