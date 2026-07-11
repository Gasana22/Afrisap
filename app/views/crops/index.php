<?php use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Crop Management</h4>
    <div class="d-flex gap-2">
        <a href="/crops/setup" class="btn btn-outline-secondary"><i class="bi bi-gear"></i> Crop Setup</a>
        <?php if (Auth::hasPermission('crops.create')): ?>
        <a href="/crops/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Start Crop Cycle</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Batch Code</th><th>Crop</th><th>Farm / Block / Plot</th><th>Season</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($cycles)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No crop cycles yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($cycles as $c): ?>
                <tr>
                    <td><a href="/crops/<?= (int) $c['id'] ?>" class="font-monospace"><?= htmlspecialchars($c['batch_code']) ?></a></td>
                    <td><?= htmlspecialchars($c['crop_type_name']) ?></td>
                    <td class="small"><?= htmlspecialchars($c['farm_name']) ?> / <?= htmlspecialchars($c['block_name']) ?> / <?= htmlspecialchars($c['plot_code']) ?></td>
                    <td><?= htmlspecialchars($c['season_name'] ?? '—') ?></td>
                    <td><span class="badge bg-info text-dark text-capitalize"><?= htmlspecialchars($c['status']) ?></span></td>
                    <td class="text-end"><a href="/crops/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $baseUrl = '/crops'; require __DIR__ . '/../partials/pagination.php'; ?>
