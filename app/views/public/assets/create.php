<h4 class="mb-4">Add Asset</h4>

<?php if (empty($farms)): ?>
<div class="alert alert-warning">You need at least one <a href="/farms">farm</a> before adding an asset.</div>
<?php else: ?>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/farm-assets">
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
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="vehicle">Vehicle</option>
                        <option value="machinery">Machinery</option>
                        <option value="building">Building</option>
                        <option value="irrigation">Irrigation</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Identifier / Serial</label><input type="text" name="identifier" class="form-control"></div>
            </div>
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required placeholder="e.g. Toyota Hilux, Water Pump #2"></div>
            <div class="row g-3">
                <div class="col-md-6 mb-3"><label class="form-label">Purchase date</label><input type="date" name="purchase_date" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Purchase value</label><input type="text" name="purchase_value" class="form-control"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="under_maintenance">Under Maintenance</option>
                    <option value="retired">Retired</option>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <button type="submit" class="btn btn-success">Add Asset</button>
            <a href="/farm-assets" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>
