<h4 class="mb-4">Edit <?= htmlspecialchars($item['name']) ?></h4>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/inventory/<?= (int) $item['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required></div>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach (['seed', 'fertilizer', 'chemical', 'equipment', 'other'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= $item['category'] === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($item['unit'] ?? '') ?>"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Reorder level</label>
                <input type="text" name="reorder_level" class="form-control" value="<?= htmlspecialchars($item['reorder_level'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="/inventory/<?= (int) $item['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
