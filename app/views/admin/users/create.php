<h4 class="mb-4">Add User</h4>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/admin/users">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Temporary password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="mfa_enabled" value="1" class="form-check-input" id="mfaEnabled" checked>
                <label class="form-check-label" for="mfaEnabled">Require email MFA on login</label>
            </div>
            <button type="submit" class="btn btn-success">Create User</button>
            <a href="/admin/users" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
