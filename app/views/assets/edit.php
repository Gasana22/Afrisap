<h4 class="mb-4">Edit <?= htmlspecialchars($asset['name']) ?></h4>

<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/farm-assets/<?= (int) $asset['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <?php foreach (['vehicle', 'machinery', 'building', 'irrigation'] as $type): ?>
                        <option value="<?= $type ?>" <?= $asset['type'] === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Identifier / Serial</label><input type="text" name="identifier" class="form-control" value="<?= htmlspecialchars($asset['identifier'] ?? '') ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($asset['name']) ?>" required></div>
            <div class="row g-3">
                <div class="col-md-6 mb-3"><label class="form-label">Purchase date</label><input type="date" name="purchase_date" class="form-control" value="<?= htmlspecialchars($asset['purchase_date'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Purchase value</label><input type="text" name="purchase_value" class="form-control" value="<?= htmlspecialchars($asset['purchase_value'] ?? '') ?>"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['active' => 'Active', 'under_maintenance' => 'Under Maintenance', 'retired' => 'Retired'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $asset['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($asset['notes'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="/farm-assets/<?= (int) $asset['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
