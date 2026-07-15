<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$totalIncome = (float) db_value(
    "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE organization_id = :id AND type = 'income' AND transaction_date BETWEEN :date_from AND :date_to",
    ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
);
$totalExpense = (float) db_value(
    "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE organization_id = :id AND type = 'expense' AND transaction_date BETWEEN :date_from AND :date_to",
    ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
);
$netProfit = $totalIncome - $totalExpense;

$incomeByCategory = db_all(
    "SELECT category AS label, SUM(amount) AS total FROM financial_transactions
     WHERE organization_id = :id AND type = 'income' AND transaction_date BETWEEN :date_from AND :date_to
     GROUP BY category ORDER BY total DESC",
    ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
);
$expenseByCategory = db_all(
    "SELECT category AS label, SUM(amount) AS total FROM financial_transactions
     WHERE organization_id = :id AND type = 'expense' AND transaction_date BETWEEN :date_from AND :date_to
     GROUP BY category ORDER BY total DESC",
    ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
);

$incomeChart = [
    'labels' => array_map(fn ($r) => humanize((string) $r['label']), $incomeByCategory),
    'data' => array_map(fn ($r) => (float) $r['total'], $incomeByCategory),
];
$expenseChart = [
    'labels' => array_map(fn ($r) => humanize((string) $r['label']), $expenseByCategory),
    'data' => array_map(fn ($r) => (float) $r['total'], $expenseByCategory),
];

$exportParams = http_build_query(['report' => 'financial', 'date_from' => $dateFrom, 'date_to' => $dateTo]);

render_header(['title' => 'Financial Report', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Financial Report</h4>
          <p class="text-muted small mb-0">Executive summary of income, expenses and net profit. See Finance &rarr; Profit &amp; Loss for a detailed breakdown.</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-download"></i> Export
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= base_url('org-admin/reporting/export.php?' . $exportParams . '&format=csv') ?>">CSV</a></li>
            <li><a class="dropdown-item" href="<?= base_url('org-admin/reporting/export.php?' . $exportParams . '&format=excel') ?>">Excel</a></li>
            <li><a class="dropdown-item" href="<?= base_url('org-admin/reporting/export.php?' . $exportParams . '&format=pdf') ?>">PDF</a></li>
          </ul>
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
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/reporting/financial.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <?php render_stat_card('Total Income', format_money($totalIncome), null, 'bi-graph-up-arrow', 'secondary'); ?>
        </div>
        <div class="col-sm-4">
          <?php render_stat_card('Total Expenses', format_money($totalExpense), null, 'bi-graph-down-arrow', 'danger'); ?>
        </div>
        <div class="col-sm-4">
          <?php render_stat_card('Net Profit', format_money($netProfit), null, 'bi-piggy-bank', $netProfit >= 0 ? 'primary' : 'danger'); ?>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Income by Category</h6></div>
            <?php if ($incomeChart['data']): ?>
              <canvas id="incomeChart" height="140"></canvas>
            <?php else: ?>
              <?php render_empty_state('No income recorded in this period.', 'bi-cash-coin'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Expenses by Category</h6></div>
            <?php if ($expenseChart['data']): ?>
              <canvas id="expenseChart" height="140"></canvas>
            <?php else: ?>
              <?php render_empty_state('No expenses recorded in this period.', 'bi-cash-stack'); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
<?php if ($incomeChart['data']): ?>
  renderDoughnutChart('incomeChart', <?= json_encode($incomeChart['labels']) ?>, <?= json_encode($incomeChart['data']) ?>);
<?php endif; ?>
<?php if ($expenseChart['data']): ?>
  renderDoughnutChart('expenseChart', <?= json_encode($expenseChart['labels']) ?>, <?= json_encode($expenseChart['data']) ?>);
<?php endif; ?>
});
</script>
<?php render_footer(['context' => 'org-admin', 'js' => ['charts']]); ?>
