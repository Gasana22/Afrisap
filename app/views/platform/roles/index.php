<h4 class="mb-4">Roles &amp; Permissions</h4>
<p class="text-muted small mb-3">Editing a role's permissions changes it for every user assigned that role — including across every tenant organization for tenant-scoped roles.</p>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Role</th><th>Scope</th><th>Description</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= htmlspecialchars($role['name']) ?></td>
                    <td><span class="badge bg-<?= $role['scope'] === 'platform' ? 'dark' : 'success' ?>"><?= htmlspecialchars($role['scope']) ?></span></td>
                    <td class="text-muted small"><?= htmlspecialchars($role['description'] ?? '') ?></td>
                    <td class="text-end">
                        <?php if (\App\Core\Auth::hasPermission('roles.edit')): ?>
                        <a href="/platform/roles/<?= (int) $role['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Edit Permissions</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
