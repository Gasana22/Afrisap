<?php use App\Core\Auth; use App\Core\Csrf; $canEdit = Auth::hasPermission('assets.edit'); ?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($asset['name']) ?></h4>
        <p class="text-muted mb-0">
            <span class="text-capitalize"><?= htmlspecialchars($asset['type']) ?></span>
            <?= $asset['identifier'] ? ' &middot; ' . htmlspecialchars($asset['identifier']) : '' ?>
            &middot; <?= htmlspecialchars($asset['farm_name']) ?>
            <?php $statusBadge = ['active' => 'success', 'under_maintenance' => 'warning', 'retired' => 'secondary'][$asset['status']] ?? 'secondary'; ?>
            &middot; <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $asset['status'])) ?></span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEdit): ?>
        <a href="/farm-assets/<?= (int) $asset['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('assets.delete')): ?>
        <form method="post" action="/farm-assets/<?= (int) $asset['id'] ?>/delete" onsubmit="return confirm('Delete this asset? Only possible with no maintenance history.');">
            <?= Csrf::field() ?><button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Purchase Date</div><div class="fs-6 fw-bold"><?= htmlspecialchars($asset['purchase_date'] ?? '—') ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Purchase Value</div><div class="fs-6 fw-bold"><?= $asset['purchase_value'] !== null ? number_format((float) $asset['purchase_value'], 2) : '—' ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Next Maintenance Due</div><div class="fs-6 fw-bold"><?= htmlspecialchars($asset['next_maintenance_due'] ?? '—') ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Total Maintenance Cost</div><div class="fs-6 fw-bold"><?= number_format((float) array_sum(array_column($maintenanceRecords, 'cost')), 2) ?></div></div></div>
</div>

<?php if ($asset['notes']): ?><p class="text-muted small">Notes: <?= htmlspecialchars($asset['notes']) ?></p><?php endif; ?>

<div class="card">
    <div class="card-body">
        <h6 class="mb-3">Maintenance History</h6>
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Description</th><th>Performed By</th><th class="text-end">Cost</th><th>Next Due</th></tr></thead>
            <tbody>
                <?php if (empty($maintenanceRecords)): ?><tr><td colspan="5" class="text-muted small">No maintenance recorded yet.</td></tr><?php endif; ?>
                <?php foreach ($maintenanceRecords as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['maintenance_date']) ?></td>
                    <td><?= htmlspecialchars($m['description']) ?></td>
                    <td><?= htmlspecialchars($m['performed_by'] ?? '—') ?></td>
                    <td class="text-end"><?= $m['cost'] !== null ? number_format((float) $m['cost'], 2) : '—' ?></td>
                    <td><?= htmlspecialchars($m['next_due_date'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($canEdit): ?>
        <form method="post" action="/farm-assets/<?= (int) $asset['id'] ?>/maintenance" class="row g-2">
            <?= Csrf::field() ?>
            <div class="col-md-2"><input type="date" name="maintenance_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-3"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description" required></div>
            <div class="col-md-2"><input type="text" name="performed_by" class="form-control form-control-sm" placeholder="Performed by"></div>
            <div class="col-md-2"><input type="text" name="cost" class="form-control form-control-sm" placeholder="Cost"></div>
            <div class="col-md-2"><input type="date" name="next_due_date" class="form-control form-control-sm" title="Next due date"></div>
            <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
        </form>
        <?php endif; ?>
    </div>
</div>
