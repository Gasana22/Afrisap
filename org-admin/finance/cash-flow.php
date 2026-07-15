<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner', 'manager', 'accountant']);
$organization = current_organization();
$orgId = (int) $organization['id'];

$months = (int) ($_GET['months'] ?? 12);
if (!in_array($months, [6, 12, 24], true)) {
    $months = 12;
}

$incomeSeries = chart_monthly_series($orgId, 'financial_transactions', 'transaction_date', 'amount', "AND type = 'income'", [], $months);
$expenseSeries = chart_monthly_series($orgId, 'financial_transactions', 'transaction_date', 'amount', "AND type = 'expense'", [], $months);

$labels = $incomeSeries['labels'];
$netSeries = [];
$totalIncome = 0.0;
$totalExpense = 0.0;
foreach ($labels as $i => $label) {
    $inc = $incomeSeries['data'][$i] ?? 0;
    $exp = $expenseSeries['data'][$i] ?? 0;
    $netSeries[] = $inc - $exp;
    $totalIncome += $inc;
    $totalExpense += $exp;
}
$netCashFlow = $totalIncome - $totalExpense;

render_header(['title' => 'Cash Flow Statement', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Cash Flow Statement</h4>
          <p class="text-muted small mb-0">Income vs. expenses, month by month.</p>
        </div>
        <form method="get" class="d-flex gap-2">
          <select name="months" class="form-select" onchange="this.form.submit()">
            <option value="6" <?= $months === 6 ? 'selected' : '' ?>>Last 6 months</option>
            <option value="12" <?= $months === 12 ? 'selected' : '' ?>>Last 12 months</option>
            <option value="24" <?= $months === 24 ? 'selected' : '' ?>>Last 24 months</option>
          </select>
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
          <?php render_stat_card('Net Cash Flow', format_money($netCashFlow), null, 'bi-cash-stack', $netCashFlow >= 0 ? 'primary' : 'danger'); ?>
        </div>
      </div>

      <div class="content-card mb-4">
        <div class="content-card-header"><h6>Income vs. Expenses</h6></div>
        <canvas id="cashFlowChart" height="90"></canvas>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Monthly Breakdown</h6></div>
        <div class="table-responsive">
          <table class="table table-app align-middle">
            <thead>
              <tr>
                <th>Month</th>
                <th class="text-end">Income</th>
                <th class="text-end">Expenses</th>
                <th class="text-end">Net Cash Flow</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($labels as $i => $label): ?>
                <tr>
                  <td><?= e($label) ?></td>
                  <td class="text-end text-success"><?= format_money($incomeSeries['data'][$i]) ?></td>
                  <td class="text-end text-danger"><?= format_money($expenseSeries['data'][$i]) ?></td>
                  <td class="text-end fw-semibold <?= $netSeries[$i] >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_money($netSeries[$i]) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="fw-bold">
                <td>Total</td>
                <td class="text-end"><?= format_money($totalIncome) ?></td>
                <td class="text-end"><?= format_money($totalExpense) ?></td>
                <td class="text-end <?= $netCashFlow >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_money($netCashFlow) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    renderMultiLineChart('cashFlowChart', <?= json_encode($labels) ?>, [
        { label: 'Income', data: <?= json_encode($incomeSeries['data']) ?>, color: '#1a7a4c' },
        { label: 'Expenses', data: <?= json_encode($expenseSeries['data']) ?>, color: '#dc3545' },
    ]);
});
</script>
<?php render_footer(['context' => 'org-admin', 'js' => ['charts']]); ?>
