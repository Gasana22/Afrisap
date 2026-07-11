<h4 class="mb-4">Create Purchase Order</h4>

<?php if (empty($suppliers) || empty($farms)): ?>
<div class="alert alert-warning">You need at least one <a href="/suppliers">supplier</a> and one <a href="/farms">farm</a> before creating a purchase order.</div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <form method="post" action="/purchase-orders">
            <?= \App\Core\Csrf::field() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">— Select supplier —</option>
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Farm</label>
                    <select name="farm_id" class="form-select" required>
                        <option value="">— Select farm —</option>
                        <?php foreach ($farms as $f): ?>
                        <option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Order date</label>
                    <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Expected date</label>
                    <input type="date" name="expected_date" class="form-control">
                </div>
            </div>

            <h6 class="mt-4">Line Items</h6>
            <table class="table table-sm" id="itemsTable">
                <thead><tr><th>Item</th><th>Quantity</th><th>Unit</th><th>Unit Cost</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="item_name[]" class="form-control form-control-sm" required></td>
                        <td><input type="text" name="quantity[]" class="form-control form-control-sm" required></td>
                        <td><input type="text" name="unit[]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="unit_cost[]" class="form-control form-control-sm" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addRowBtn"><i class="bi bi-plus"></i> Add Line</button>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

            <button type="submit" class="btn btn-success">Create Purchase Order</button>
            <a href="/purchase-orders" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
document.getElementById('addRowBtn').addEventListener('click', function () {
    const tbody = document.querySelector('#itemsTable tbody');
    const row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
    tbody.appendChild(row);
});
document.querySelector('#itemsTable').addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-row')) {
        const tbody = document.querySelector('#itemsTable tbody');
        if (tbody.rows.length > 1) {
            e.target.closest('tr').remove();
        }
    }
});
</script>
<?php endif; ?>
