<h4 class="mb-4">Platform Dashboard</h4>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Organizations</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['organizations'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Active Organizations</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['active_organizations'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Tenant Users</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['tenant_users'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Platform Staff</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['platform_users'] ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Recent Organizations</h6>
            <a href="/platform/organizations" class="small">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead><tr><th>Name</th><th>Owner</th><th>Farms</th><th>Users</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                    <?php if (empty($recentOrganizations)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No organizations yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentOrganizations as $o): ?>
                    <tr>
                        <td><a href="/platform/organizations/<?= (int) $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></a></td>
                        <td><?= htmlspecialchars($o['owner_name'] ?? '—') ?></td>
                        <td><?= (int) $o['farm_count'] ?></td>
                        <td><?= (int) $o['user_count'] ?></td>
                        <td><span class="badge bg-<?= $o['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars($o['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
