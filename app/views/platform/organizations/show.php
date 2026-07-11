<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><?= htmlspecialchars($organization['name']) ?></h4>
        <p class="text-muted small mb-0">Owner: <?= htmlspecialchars($organization['owner_name'] ?? '—') ?> (<?= htmlspecialchars($organization['owner_email'] ?? '') ?>)</p>
    </div>
    <span class="badge bg-<?= $organization['status'] === 'active' ? 'success' : 'secondary' ?> fs-6"><?= htmlspecialchars($organization['status']) ?></span>
</div>

<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div class="text-muted small">Created <?= htmlspecialchars($organization['created_at']) ?></div>
        <form method="post" action="/platform/organizations/<?= (int) $organization['id'] ?>/status"
            onsubmit="return confirm('<?= $organization['status'] === 'active' ? 'Suspend' : 'Reactivate' ?> this organization?');">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="status" value="<?= $organization['status'] === 'active' ? 'suspended' : 'active' ?>">
            <button type="submit" class="btn btn-sm btn-outline-<?= $organization['status'] === 'active' ? 'danger' : 'success' ?>">
                <?= $organization['status'] === 'active' ? 'Suspend Organization' : 'Reactivate Organization' ?>
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="mb-3">Team Members</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead>
                <tbody>
                    <?php if (empty($members)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No members yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['name']) ?></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['role_name']) ?></td>
                        <td><span class="badge bg-<?= $m['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($m['status']) ?></span></td>
                        <td class="text-muted small"><?= $m['last_login_at'] ? htmlspecialchars($m['last_login_at']) : 'never' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
