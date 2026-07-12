<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

$farms = [];
if ($farmIds) {
    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('finance.manage');

    $type = $_POST['record_type'] ?? '';
    $farmId = (int) ($_POST['farm_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $date = $_POST['record_date'] ?: date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '') ?: null;

    if (!in_array($farmId, $farmIds, true) || $description === '' || $amount <= 0) {
        $error = 'A valid farm, description, and positive amount are required.';
    } elseif ($type === 'income') {
        db()->prepare(
            'INSERT INTO income (farm_id, description, amount, income_date, notes, recorded_by) VALUES (:farm, :desc, :amount, :date, :notes, :by)'
        )->execute(['farm' => $farmId, 'desc' => $description, 'amount' => $amount, 'date' => $date, 'notes' => $notes, 'by' => current_user()['id']]);
        flash('success', 'Income recorded.');
        redirect('/admin/finance.php');
    } elseif ($type === 'expense') {
        $category = $_POST['category'] ?? 'other';
        db()->prepare(
            'INSERT INTO expenses (farm_id, category, description, amount, expense_date, notes, recorded_by)
             VALUES (:farm, :category, :desc, :amount, :date, :notes, :by)'
        )->execute(['farm' => $farmId, 'category' => $category, 'desc' => $description, 'amount' => $amount, 'date' => $date, 'notes' => $notes, 'by' => current_user()['id']]);
        flash('success', 'Expense recorded.');
        redirect('/admin/finance.php');
    } else {
        $error = 'Unknown record type.';
    }
}

$income = $expenses = [];
$totalIncome = $totalExpenses = 0;

if ($farmIds) {
    $incomeStmt = db()->prepare(
        'SELECT i.*, f.name AS farm_name FROM income i JOIN farms f ON f.id = i.farm_id
         WHERE i.farm_id IN (' . in_placeholders($farmIds) . ') ORDER BY i.income_date DESC LIMIT 50'
    );
    $incomeStmt->execute($farmIds);
    $income = $incomeStmt->fetchAll();

    $expenseStmt = db()->prepare(
        'SELECT e.*, f.name AS farm_name FROM expenses e JOIN farms f ON f.id = e.farm_id
         WHERE e.farm_id IN (' . in_placeholders($farmIds) . ') ORDER BY e.expense_date DESC LIMIT 50'
    );
    $expenseStmt->execute($farmIds);
    $expenses = $expenseStmt->fetchAll();

    $totalIncomeStmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM income WHERE farm_id IN (' . in_placeholders($farmIds) . ')');
    $totalIncomeStmt->execute($farmIds);
    $totalIncome = (float) $totalIncomeStmt->fetchColumn();

    $totalExpenseStmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE farm_id IN (' . in_placeholders($farmIds) . ')');
    $totalExpenseStmt->execute($farmIds);
    $totalExpenses = (float) $totalExpenseStmt->fetchColumn();
}

$pageTitle = 'Finance';
$activePage = 'finance';
require __DIR__ . '/includes/header.php';
?>

<h1>Finance</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="stat-grid">
    <div class="card"><div class="number"><?= number_format($totalIncome, 2) ?></div><div>Total income</div></div>
    <div class="card"><div class="number"><?= number_format($totalExpenses, 2) ?></div><div>Total expenses</div></div>
    <div class="card"><div class="number"><?= number_format($totalIncome - $totalExpenses, 2) ?></div><div>Net</div></div>
</div>

<?php if (!$farms): ?>
    <div class="alert alert-error">No farms available yet.</div>
<?php else: ?>

<div class="card" style="margin-bottom:1.5rem; max-width:460px;">
    <h2 style="margin-top:0; font-size:1rem;">Record income or an expense</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/finance.php">
        <label>Type</label>
        <select name="record_type" id="record_type" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;" onchange="document.getElementById('category_field').style.display = this.value === 'expense' ? 'block' : 'none';">
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select>
        <label>Farm</label>
        <select name="farm_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <?php foreach ($farms as $f): ?>
                <option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div id="category_field" style="display:none;">
            <label>Category</label>
            <select name="category" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="input">Input</option><option value="payroll">Payroll</option>
                <option value="maintenance">Maintenance</option><option value="procurement">Procurement</option><option value="other">Other</option>
            </select>
        </div>
        <label>Description</label>
        <input type="text" name="description" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Amount</label>
        <input type="number" step="0.01" name="amount" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Date</label>
        <input type="date" name="record_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Notes</label>
        <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Save</button>
    </form>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Income</h2>
    <table>
        <thead><tr><th>Date</th><th>Farm</th><th>Description</th><th>Amount</th></tr></thead>
        <tbody>
            <?php if (!$income): ?><tr><td colspan="4">No income recorded yet.</td></tr><?php endif; ?>
            <?php foreach ($income as $row): ?>
                <tr><td><?= e($row['income_date']) ?></td><td><?= e($row['farm_name']) ?></td><td><?= e($row['description']) ?></td><td><?= e($row['amount']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Expenses</h2>
    <table>
        <thead><tr><th>Date</th><th>Farm</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
        <tbody>
            <?php if (!$expenses): ?><tr><td colspan="5">No expenses recorded yet.</td></tr><?php endif; ?>
            <?php foreach ($expenses as $row): ?>
                <tr><td><?= e($row['expense_date']) ?></td><td><?= e($row['farm_name']) ?></td><td><?= e($row['category']) ?></td><td><?= e($row['description']) ?></td><td><?= e($row['amount']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
