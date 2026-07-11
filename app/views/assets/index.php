<?php use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Assets</h4>
    <?php if (Auth::hasPermission('assets.create')): ?>
    <a href="/farm-assets/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Asset</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Name</th><th>Type</th><th>Farm</th><th>Purchase Value</th><th>Status</th><th>Next Maintenance Due</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($assets)): ?><tr><td colspan="7" class="text-center text-muted py-4">No assets yet.</td></tr><?php endif; ?>
                <?php foreach ($assets as $a): ?>
                <tr>
                    <td><a href="/farm-assets/<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></a><?= $a['identifier'] ? ' <span class="text-muted small">(' . htmlspecialchars($a['identifier']) . ')</span>' : '' ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($a['type']) ?></td>
                    <td><?= htmlspecialchars($a['farm_name']) ?></td>
                    <td><?= $a['purchase_value'] !== null ? number_format((float) $a['purchase_value'], 2) : '—' ?></td>
                    <td>
                        <?php $statusBadge = ['active' => 'success', 'under_maintenance' => 'warning', 'retired' => 'secondary'][$a['status']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $a['status'])) ?></span>
                    </td>
                    <td><?= htmlspecialchars($a['next_maintenance_due'] ?? '—') ?></td>
                    <td class="text-end"><a href="/farm-assets/<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
