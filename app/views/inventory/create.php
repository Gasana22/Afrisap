<h4 class="mb-4">Add Inventory Item</h4>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/inventory">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="seed">Seed</option><option value="fertilizer">Fertilizer</option>
                        <option value="chemical">Chemical</option><option value="equipment">Equipment</option>
                        <option value="other" selected>Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" placeholder="e.g. kg, litres, units"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Reorder level <span class="text-muted small">(low-stock alert threshold)</span></label>
                <input type="text" name="reorder_level" class="form-control">
            </div>
            <button type="submit" class="btn btn-success">Add Item</button>
            <a href="/inventory" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
