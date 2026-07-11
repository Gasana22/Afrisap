<h4 class="mb-4">My Profile</h4>

<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/profile">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($profileUser['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profileUser['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($profileUser['phone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($profileUser['role_name']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">New password <span class="text-muted small">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="form-control" minlength="8">
            </div>
            <button type="submit" class="btn btn-success">Save changes</button>
        </form>
    </div>
</div>
