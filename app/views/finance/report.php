<?php use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0">Finance</h4>
    <div class="d-flex gap-2">
        <a href="/finance/income" class="btn btn-outline-secondary btn-sm">Income Ledger</a>
        <a href="/finance/expenses" class="btn btn-outline-secondary btn-sm">Expense Ledger</a>
        <?php $exportQuery = http_build_query(['farm_id' => $selectedFarmId, 'from' => $from, 'to' => $to]); ?>
        <a href="/finance?<?= $exportQuery ?>&format=pdf" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <a href="/finance?<?= $exportQuery ?>&format=excel" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    </div>
</div>

<form method="get" action="/finance" class="row g-2 mb-4">
    <div class="col-md-3">
        <select name="farm_id" class="form-select form-select-sm">
            <option value="">All Farms</option>
            <?php foreach ($farms as $farm): ?>
            <option value="<?= (int) $farm['id'] ?>" <?= $selectedFarmId === (int) $farm['id'] ? 'selected' : '' ?>><?= htmlspecialchars($farm['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from ?? '') ?>" placeholder="From"></div>
    <div class="col-md-3"><input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to ?? '') ?>" placeholder="To"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-success w-100">Filter</button></div>
</form>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted small">Total Income</div>
            <div class="fs-4 fw-bold text-success"><?= number_format((float) $summary['total_income'], 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted small">Total Expenses</div>
            <div class="fs-4 fw-bold text-danger"><?= number_format((float) $summary['total_expenses'], 2) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card p-3">
            <div class="text-muted small">Net Profit / Loss (Cash Flow)</div>
            <div class="fs-4 fw-bold <?= $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format((float) $summary['net_profit'], 2) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Income Breakdown</h6>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Source</th><th>Farm</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <?php if (empty($incomeEntries)): ?><tr><td colspan="4" class="text-muted small">No income recorded in this range.</td></tr><?php endif; ?>
                            <?php foreach ($incomeEntries as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['entry_date']) ?></td>
                                <td><?= htmlspecialchars($e['source']) ?></td>
                                <td class="small"><?= htmlspecialchars($e['farm_name']) ?></td>
                                <td class="text-end"><?= number_format((float) $e['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Expense Breakdown</h6>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Source</th><th>Farm</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <?php if (empty($expenseEntries)): ?><tr><td colspan="4" class="text-muted small">No expenses recorded in this range.</td></tr><?php endif; ?>
                            <?php foreach ($expenseEntries as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['entry_date']) ?></td>
                                <td><?= htmlspecialchars($e['source']) ?></td>
                                <td class="small"><?= htmlspecialchars($e['farm_name']) ?></td>
                                <td class="text-end"><?= number_format((float) $e['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<p class="text-muted small mt-3">
    Income automatically includes crop sales and livestock sales. Expenses automatically include crop input
    (seed/fertilizer/chemical) costs and paid worker payroll. Use the ledgers for anything else (equipment
    rental, land lease, fuel, etc.).
</p>
