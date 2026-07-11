<?php use App\Core\Auth; use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Users</h4>
    <?php if (Auth::hasPermission('users.create')): ?>
    <a href="/admin/users/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add User</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>MFA</th><th>Status</th><th>Last Login</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role_name']) ?></td>
                    <td><?= $u['mfa_enabled'] ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-secondary">Off</span>' ?></td>
                    <td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                    <td class="text-muted small"><?= $u['last_login_at'] ? htmlspecialchars($u['last_login_at']) : 'never' ?></td>
                    <td class="text-end">
                        <?php if (Auth::hasPermission('users.edit')): ?>
                        <a href="/admin/users/<?= (int) $u['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <?php endif; ?>
                        <?php if (Auth::hasPermission('users.delete') && (int) $u['id'] !== Auth::id()): ?>
                        <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Toggle status for this user?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
