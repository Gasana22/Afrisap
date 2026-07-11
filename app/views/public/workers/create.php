<h4 class="mb-4">Add Worker</h4>

<?php if (empty($farms)): ?>
<div class="alert alert-warning">You need at least one <a href="/farms">farm</a> before adding a worker.</div>
<?php else: ?>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/workers">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Farm</label>
                <select name="farm_id" class="form-select" required>
                    <option value="">— Select farm —</option>
                    <?php foreach ($farms as $farm): ?>
                    <option value="<?= (int) $farm['id'] ?>"><?= htmlspecialchars($farm['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role / Title</label>
                    <input type="text" name="role_title" class="form-control" placeholder="e.g. Field Worker, Supervisor">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hire date</label>
                    <input type="date" name="hire_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pay rate</label>
                    <input type="text" name="pay_rate" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rate type</label>
                    <select name="pay_rate_type" class="form-select">
                        <option value="daily">Daily</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-success">Add Worker</button>
                <a href="/workers" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
