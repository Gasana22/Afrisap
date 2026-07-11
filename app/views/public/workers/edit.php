<h4 class="mb-4">Edit <?= htmlspecialchars($worker['name']) ?></h4>

<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/workers/<?= (int) $worker['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($worker['name']) ?>" required>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($worker['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role / Title</label>
                    <input type="text" name="role_title" class="form-control" value="<?= htmlspecialchars($worker['role_title'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hire date</label>
                    <input type="date" name="hire_date" class="form-control" value="<?= htmlspecialchars($worker['hire_date'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pay rate</label>
                    <input type="text" name="pay_rate" class="form-control" value="<?= htmlspecialchars($worker['pay_rate'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rate type</label>
                    <select name="pay_rate_type" class="form-select">
                        <option value="daily" <?= $worker['pay_rate_type'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="monthly" <?= $worker['pay_rate_type'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="/workers/<?= (int) $worker['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
