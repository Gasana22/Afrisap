<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner', 'manager', 'accountant']);
$organization = current_organization();
$orgId = (int) $organization['id'];

const INCOME_CATEGORIES = ['Crop Sales', 'Livestock Sales', 'Other'];

if (is_post() && ($_POST['_method'] ?? '') === 'DELETE' && csrf_verify()) {
    $id = clean_int($_POST['id'] ?? 0);
    $old = tenant_find('financial_transactions', $orgId, $id);
    if ($old) {
        tenant_delete('financial_transactions', $orgId, $id);
        audit_log($orgId, $currentUser['id'], 'delete', 'financial_transactions', $id, $old, null);
        session_flash('success', 'Income record deleted.');
    }
    redirect('org-admin/finance/income.php');
}

$errors = [];
$input = [
    'category' => '',
    'sub_category' => '',
    'amount' => '',
    'description' => '',
    'transaction_date' => date('Y-m-d'),
];

if (is_post() && ($_POST['_method'] ?? '') !== 'DELETE' && csrf_verify()) {
    $input = [
        'category' => clean_string($_POST['category'] ?? ''),
        'sub_category' => clean_string($_POST['sub_category'] ?? ''),
        'amount' => $_POST['amount'] ?? '',
        'description' => clean_string($_POST['description'] ?? ''),
        'transaction_date' => $_POST['transaction_date'] ?? '',
    ];

    $errors = validate($input, [
        'category' => 'required|max:100',
        'amount' => 'required|numeric',
        'transaction_date' => 'required|date',
    ]);

    $receiptPath = null;
    if (!$errors && !empty($_FILES['receipt']['name'])) {
        try {
            $receiptPath = handle_upload($_FILES['receipt'], $orgId, 'receipts');
        } catch (RuntimeException $e) {
            $errors['receipt'] = $e->getMessage();
        }
    }

    if (!$errors) {
        $id = tenant_insert('financial_transactions', $orgId, [
            'type' => 'income',
            'category' => $input['category'],
            'sub_category' => $input['sub_category'] ?: null,
            'amount' => clean_float($input['amount']),
            'description' => $input['description'],
            'transaction_date' => $input['transaction_date'],
            'receipt_url' => $receiptPath,
            'created_by' => $currentUser['id'],
        ]);
        audit_log($orgId, $currentUser['id'], 'create', 'financial_transactions', $id, null, $input);
        session_flash('success', 'Income transaction recorded.');
        redirect('org-admin/finance/income.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

// --- Filters ---
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$whereSql = '';
$params = [];
if ($dateFrom !== '') {
    $whereSql .= ' AND transaction_date >= :date_from';
    $params['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $whereSql .= ' AND transaction_date <= :date_to';
    $params['date_to'] = $dateTo;
}
if ($categoryFilter !== '') {
    $whereSql .= ' AND category = :category';
    $params['category'] = $categoryFilter;
}

$transactions = tenant_all(
    'financial_transactions',
    $orgId,
    "AND type = 'income' $whereSql ORDER BY transaction_date DESC, id DESC",
    $params
);

$existingCategories = db_all(
    "SELECT DISTINCT category FROM financial_transactions WHERE organization_id = :org_id AND type = 'income' ORDER BY category",
    ['org_id' => $orgId]
);

$monthlyTotal = (float) db_value(
    "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE organization_id = :org_id AND type = 'income' AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
    ['org_id' => $orgId]
);

$filteredTotal = 0.0;
foreach ($transactions as $t) {
    $filteredTotal += (float) $t['amount'];
}

render_header(['title' => 'Income', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Income</h4>
          <p class="text-muted small mb-0">Track and review income transactions for your organization.</p>
        </div>
        <a href="<?= base_url('org-admin/finance/expenses.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-cash-stack"></i> View Expenses</a>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
          <?php render_stat_card('This Month\'s Income', format_money($monthlyTotal), null, 'bi-graph-up-arrow', 'secondary'); ?>
        </div>
        <div class="col-sm-6 col-xl-4">
          <?php render_stat_card('Filtered Total', format_money($filteredTotal), null, 'bi-funnel', 'info'); ?>
        </div>
        <div class="col-sm-6 col-xl-4">
          <?php render_stat_card('Transactions Shown', (string) count($transactions), null, 'bi-list-ol', 'primary'); ?>
        </div>
      </div>

      <div class="content-card mb-4">
        <h5 class="mb-3">Record New Income</h5>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Category</label>
              <input type="text" name="category" class="form-control" list="incomeCategories" required value="<?= e($input['category']) ?>">
              <datalist id="incomeCategories">
                <?php foreach (INCOME_CATEGORIES as $cat): ?>
                  <option value="<?= e($cat) ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Sub-category <span class="text-muted small">(optional)</span></label>
              <input type="text" name="sub_category" class="form-control" value="<?= e($input['sub_category']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Amount</label>
              <input type="number" step="0.01" min="0" name="amount" class="form-control" required value="<?= e((string) $input['amount']) ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Transaction Date</label>
              <input type="date" name="transaction_date" class="form-control" required value="<?= e($input['transaction_date']) ?>">
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label">Description <span class="text-muted small">(optional)</span></label>
              <input type="text" name="description" class="form-control" value="<?= e($input['description']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Receipt <span class="text-muted small">(optional)</span></label>
            <input type="file" name="receipt" class="form-control">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Save Income</button>
        </form>
      </div>

      <div class="content-card mb-4">
        <h6 class="mb-3">Filter</h6>
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">From</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">To</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Category</label>
            <select name="category" class="form-select">
              <option value="">All Categories</option>
              <?php foreach ($existingCategories as $c): ?>
                <option value="<?= e($c['category']) ?>" <?= $categoryFilter === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/finance/income.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Income Transactions</h6></div>
        <?php if (!$transactions): ?>
          <?php render_empty_state('No income transactions recorded yet.', 'bi-cash-coin'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Category</th>
                  <th>Sub-category</th>
                  <th>Description</th>
                  <th class="text-end">Amount</th>
                  <th class="text-end">Running Total</th>
                  <th>Receipt</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php
                $running = 0.0;
                $rowsChrono = array_reverse($transactions);
                $runningById = [];
                foreach ($rowsChrono as $t) {
                    $running += (float) $t['amount'];
                    $runningById[$t['id']] = $running;
                }
                ?>
                <?php foreach ($transactions as $t): ?>
                  <tr>
                    <td><?= format_date($t['transaction_date']) ?></td>
                    <td><?= e($t['category']) ?></td>
                    <td><?= e($t['sub_category'] ?: '-') ?></td>
                    <td><?= e($t['description'] ?: '-') ?></td>
                    <td class="text-end text-success fw-semibold"><?= format_money((float) $t['amount']) ?></td>
                    <td class="text-end"><?= format_money($runningById[$t['id']]) ?></td>
                    <td>
                      <?php if ($t['receipt_url']): ?>
                        <a href="<?= e(uploaded_file_url($t['receipt_url'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-paperclip"></i></a>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteIncome<?= $t['id'] ?>"><i class="bi bi-trash"></i></button>
                    </td>
                  </tr>
                  <?php confirm_delete_modal('deleteIncome' . $t['id'], base_url('org-admin/finance/income.php'), 'this income transaction', ['id' => $t['id']]); ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
