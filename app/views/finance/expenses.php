<?php use App\Core\Auth; use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Expense Ledger</h4>
    <a href="/finance" class="btn btn-outline-secondary btn-sm">Back to Finance Report</a>
</div>

<?php if (Auth::hasPermission('finance.create')): ?>
<div class="card mb-4">
    <div class="card-body">
        <h6 class="small text-muted">Record Manual Expense</h6>
        <form method="post" action="/finance/expenses" class="row g-2">
            <?= Csrf::field() ?>
            <div class="col-md-2">
                <select name="farm_id" class="form-select form-select-sm" required>
                    <option value="">Farm</option>
                    <?php foreach ($farms as $farm): ?>
                    <option value="<?= (int) $farm['id'] ?>"><?= htmlspecialchars($farm['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="maintenance">Maintenance</option>
                    <option value="procurement">Procurement</option>
                    <option value="input">Input</option>
                    <option value="payroll">Payroll</option>
                    <option value="other" selected>Other</option>
                </select>
            </div>
            <div class="col-md-2"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description" required></div>
            <div class="col-md-2"><input type="text" name="amount" class="form-control form-control-sm" placeholder="Amount" required></div>
            <div class="col-md-2"><input type="date" name="expense_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-1"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
            <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Date</th><th>Farm</th><th>Category</th><th>Description</th><th>Recorded By</th><th class="text-end">Amount</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($entries)): ?><tr><td colspan="7" class="text-center text-muted py-4">No manual expense entries yet.</td></tr><?php endif; ?>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['expense_date']) ?></td>
                    <td><?= htmlspecialchars($e['farm_name']) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($e['category']) ?></td>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td class="small"><?= htmlspecialchars($e['recorded_by_name']) ?></td>
                    <td class="text-end"><?= number_format((float) $e['amount'], 2) ?></td>
                    <td>
                        <?php if (Auth::hasPermission('finance.delete')): ?>
                        <form method="post" action="/expenses/<?= (int) $e['id'] ?>/delete" onsubmit="return confirm('Delete this expense entry?');">
                            <?= Csrf::field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
