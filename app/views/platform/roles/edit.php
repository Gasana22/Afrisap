<h4 class="mb-4">Edit Role: <?= htmlspecialchars($role['name']) ?> <span class="badge bg-<?= $role['scope'] === 'platform' ? 'dark' : 'success' ?> align-middle"><?= htmlspecialchars($role['scope']) ?></span></h4>

<form method="post" action="/platform/roles/<?= (int) $role['id'] ?>">
    <?= \App\Core\Csrf::field() ?>
    <div class="row g-3">
        <?php foreach ($permissionGroups as $module => $permissions): ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-capitalize mb-3"><?= htmlspecialchars($module) ?></h6>
                    <?php foreach ($permissions as $perm): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="permissions[]" value="<?= (int) $perm['id'] ?>"
                            id="perm<?= (int) $perm['id'] ?>"
                            <?= in_array((int) $perm['id'], $assignedPermissionIds, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="perm<?= (int) $perm['id'] ?>"><?= htmlspecialchars($perm['description'] ?? $perm['code']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-dark">Save Permissions</button>
        <a href="/platform/roles" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
