<?php use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Workers</h4>
    <?php if (Auth::hasPermission('workers.create')): ?>
    <a href="/workers/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Worker</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Name</th><th>Role</th><th>Farm</th><th>Phone</th><th>Pay Rate</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($workers)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No workers yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($workers as $w): ?>
                <tr>
                    <td><a href="/workers/<?= (int) $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></a></td>
                    <td><?= htmlspecialchars($w['role_title'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($w['farm_name']) ?></td>
                    <td><?= htmlspecialchars($w['phone'] ?? '—') ?></td>
                    <td><?= $w['pay_rate'] !== null ? htmlspecialchars($w['pay_rate']) . ' / ' . htmlspecialchars($w['pay_rate_type']) : '—' ?></td>
                    <td><span class="badge bg-<?= $w['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($w['status']) ?></span></td>
                    <td class="text-end"><a href="/workers/<?= (int) $w['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $baseUrl = '/workers'; require __DIR__ . '/../partials/pagination.php'; ?>
