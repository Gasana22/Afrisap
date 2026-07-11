<h4 class="mb-4">Edit Team Member</h4>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/team/<?= (int) $member['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($member['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>" <?= (int) $role['id'] === (int) $member['role_id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $member['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $member['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Reset password <span class="text-muted small">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="form-control" minlength="8">
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="mfa_enabled" value="1" class="form-check-input" id="mfaEnabled" <?= $member['mfa_enabled'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="mfaEnabled">Require email MFA on login</label>
            </div>
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="/team" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
