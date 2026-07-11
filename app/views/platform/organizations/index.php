<h4 class="mb-4">Organizations</h4>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Name</th><th>Owner</th><th>Farms</th><th>Users</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($organizations)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No organizations yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($organizations as $o): ?>
                <tr>
                    <td><?= htmlspecialchars($o['name']) ?></td>
                    <td><?= htmlspecialchars($o['owner_name'] ?? '—') ?> <span class="text-muted small"><?= htmlspecialchars($o['owner_email'] ?? '') ?></span></td>
                    <td><?= (int) $o['farm_count'] ?></td>
                    <td><?= (int) $o['user_count'] ?></td>
                    <td><span class="badge bg-<?= $o['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                    <td class="text-muted small"><?= htmlspecialchars($o['created_at']) ?></td>
                    <td class="text-end"><a href="/platform/organizations/<?= (int) $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
