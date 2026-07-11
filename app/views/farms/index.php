<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Farm Structure</h4>
    <?php if (\App\Core\Auth::hasPermission('farms.create')): ?>
    <a href="/farms/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Farm</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>District / Village</th>
                    <th>Size (ha)</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($farms)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No farms yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($farms as $farm): ?>
                <tr>
                    <td><a href="/farms/<?= (int) $farm['id'] ?>"><?= htmlspecialchars($farm['name']) ?></a></td>
                    <td><?= htmlspecialchars(trim(($farm['district'] ?? '') . ' / ' . ($farm['village'] ?? ''), ' /')) ?: '—' ?></td>
                    <td><?= $farm['size_hectares'] !== null ? htmlspecialchars($farm['size_hectares']) : '—' ?></td>
                    <td><?= htmlspecialchars($farm['owner_name'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $farm['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($farm['status']) ?></span></td>
                    <td class="text-end">
                        <a href="/farms/<?= (int) $farm['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
