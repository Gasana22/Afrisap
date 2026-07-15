<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner', 'manager', 'accountant']);
$organization = current_organization();
$orgId = (int) $organization['id'];

// --- Period selection (default: current calendar year) ---
$dateFrom = $_GET['date_from'] ?? date('Y-01-01');
$dateTo = $_GET['date_to'] ?? date('Y-12-31');

$groupedRows = db_all(
    "SELECT type, category, SUM(amount) AS total
     FROM financial_transactions
     WHERE organization_id = :org_id AND transaction_date BETWEEN :date_from AND :date_to
     GROUP BY type, category
     ORDER BY type, category",
    ['org_id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
);

$incomeRows = [];
$expenseRows = [];
$totalIncome = 0.0;
$totalExpense = 0.0;
foreach ($groupedRows as $row) {
    $total = (float) $row['total'];
    if ($row['type'] === 'income') {
        $incomeRows[] = ['category' => $row['category'], 'total' => $total];
        $totalIncome += $total;
    } else {
        $expenseRows[] = ['category' => $row['category'], 'total' => $total];
        $totalExpense += $total;
    }
}
$netProfit = $totalIncome - $totalExpense;

// --- Export handling (before any HTML output) ---
$exportType = $_GET['export'] ?? '';
if ($exportType !== '') {
    require_once ROOT_PATH . '/includes/export.php';

    $headers = ['Section', 'Category', 'Amount'];
    $rows = [];
    foreach ($incomeRows as $r) {
        $rows[] = ['Income', $r['category'], number_format($r['total'], 2)];
    }
    $rows[] = ['Income', 'Total Income', number_format($totalIncome, 2)];
    foreach ($expenseRows as $r) {
        $rows[] = ['Expense', $r['category'], number_format($r['total'], 2)];
    }
    $rows[] = ['Expense', 'Total Expenses', number_format($totalExpense, 2)];
    $rows[] = ['Net', 'Net Profit', number_format($netProfit, 2)];

    $filename = 'profit-loss-' . $dateFrom . '-to-' . $dateTo;

    if ($exportType === 'csv') {
        export_csv($headers, $rows, $filename);
    } elseif ($exportType === 'excel') {
        export_excel($headers, $rows, $filename, 'Profit & Loss');
    } elseif ($exportType === 'pdf') {
        $html = '<h2>Profit &amp; Loss Statement</h2>'
            . '<p>' . e($organization['name']) . ' &middot; ' . e($dateFrom) . ' to ' . e($dateTo) . '</p>'
            . '<h3>Income</h3><table border="1" cellpadding="4" cellspacing="0" width="100%">';
        foreach ($incomeRows as $r) {
            $html .= '<tr><td>' . e($r['category']) . '</td><td align="right">' . format_money($r['total']) . '</td></tr>';
        }
        $html .= '<tr><td><strong>Total Income</strong></td><td align="right"><strong>' . format_money($totalIncome) . '</strong></td></tr></table>';
        $html .= '<h3>Expenses</h3><table border="1" cellpadding="4" cellspacing="0" width="100%">';
        foreach ($expenseRows as $r) {
            $html .= '<tr><td>' . e($r['category']) . '</td><td align="right">' . format_money($r['total']) . '</td></tr>';
        }
        $html .= '<tr><td><strong>Total Expenses</strong></td><td align="right"><strong>' . format_money($totalExpense) . '</strong></td></tr></table>';
        $html .= '<h3>Net Profit: ' . format_money($netProfit) . '</h3>';
        export_pdf($html, $filename);
    }
}

render_header(['title' => 'Profit & Loss', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Profit &amp; Loss Statement</h4>
          <p class="text-muted small mb-0">Income and expenses grouped by category for the selected period.</p>
        </div>
      </div>

      <div class="content-card mb-4">
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
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Apply Period</button>
          </div>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
          <?php render_stat_card('Total Income', format_money($totalIncome), null, 'bi-graph-up-arrow', 'secondary'); ?>
        </div>
        <div class="col-sm-6 col-xl-4">
          <?php render_stat_card('Total Expenses', format_money($totalExpense), null, 'bi-graph-down-arrow', 'danger'); ?>
        </div>
        <div class="col-sm-6 col-xl-4">
          <?php render_stat_card('Net Profit', format_money($netProfit), null, 'bi-cash-stack', $netProfit >= 0 ? 'primary' : 'danger'); ?>
        </div>
      </div>

      <div class="content-card mb-4">
        <div class="content-card-header"><h6>Income</h6></div>
        <?php if (!$incomeRows): ?>
          <?php render_empty_state('No income recorded for this period.'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <tbody>
                <?php foreach ($incomeRows as $r): ?>
                  <tr>
                    <td><?= e($r['category']) ?></td>
                    <td class="text-end"><?= format_money($r['total']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="fw-bold border-top">
                  <td>Total Income</td>
                  <td class="text-end text-success"><?= format_money($totalIncome) ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="content-card mb-4">
        <div class="content-card-header"><h6>Expenses</h6></div>
        <?php if (!$expenseRows): ?>
          <?php render_empty_state('No expenses recorded for this period.'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <tbody>
                <?php foreach ($expenseRows as $r): ?>
                  <tr>
                    <td><?= e($r['category']) ?></td>
                    <td class="text-end"><?= format_money($r['total']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="fw-bold border-top">
                  <td>Total Expenses</td>
                  <td class="text-end text-danger"><?= format_money($totalExpense) ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="content-card mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Net Profit</h5>
          <h5 class="mb-0 <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_money($netProfit) ?></h5>
        </div>
      </div>

      <div class="content-card">
        <h6 class="mb-3">Export</h6>
        <div class="d-flex gap-2 flex-wrap">
          <?php
          $exportQuery = http_build_query(['date_from' => $dateFrom, 'date_to' => $dateTo]);
          ?>
          <a class="btn btn-outline-secondary" href="<?= base_url('org-admin/finance/profit-loss.php?' . $exportQuery . '&export=csv') ?>"><i class="bi bi-filetype-csv"></i> Export CSV</a>
          <a class="btn btn-outline-secondary" href="<?= base_url('org-admin/finance/profit-loss.php?' . $exportQuery . '&export=excel') ?>"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
          <a class="btn btn-outline-secondary" href="<?= base_url('org-admin/finance/profit-loss.php?' . $exportQuery . '&export=pdf') ?>"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
