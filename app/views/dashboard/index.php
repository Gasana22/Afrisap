<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Welcome, <?= htmlspecialchars($currentUser['name']) ?></h4>
        <p class="text-muted mb-0"><?= htmlspecialchars($currentUser['role_name']) ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Farms</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['farms'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Blocks</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['blocks'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Plots</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['plots'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Users</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['users'] ?></div>
        </div>
    </div>
</div>

<?php if ($analytics): ?>
<div class="viz-root">
<style>
.viz-root {
    --series-income: #2a78d6;
    --series-expense: #e34948;
    --series-primary: #2a78d6;
    --text-secondary: #52514e;
    --muted: #898781;
    --gridline: #e1e0d9;
}
/* Keyed off the app's own light/dark toggle (data-bs-theme), not the OS-level
   prefers-color-scheme -- those two can disagree, and the toggle is the one
   that actually reflects what's on screen. */
[data-bs-theme="dark"] .viz-root {
    --series-income: #3987e5;
    --series-expense: #e66767;
    --series-primary: #3987e5;
    --text-secondary: #c3c2b7;
    --muted: #898781;
    --gridline: #2c2c2a;
}
</style>

<?php
$growth = $analytics['revenueGrowth'];
$growthGood = $growth['growth_pct'] >= 0;
$mortality = $analytics['livestockMortality'];
$mortalityStatus = $mortality['rate'] <= 5 ? 'good' : ($mortality['rate'] <= 15 ? 'warning' : 'critical');
$mortalityColor = ['good' => '#0ca30c', 'warning' => '#fab219', 'critical' => '#d03b3b'][$mortalityStatus];
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Revenue Growth (MoM)</div>
            <div class="fs-4 fw-bold" style="color: <?= $growthGood ? '#0ca30c' : '#d03b3b' ?>;">
                <i class="bi bi-arrow-<?= $growthGood ? 'up' : 'down' ?>-short"></i><?= number_format($growth['growth_pct'], 1) ?>%
            </div>
            <div class="text-muted small"><?= number_format($growth['this_month'], 0) ?> this month</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Livestock Mortality Rate</div>
            <div class="fs-4 fw-bold" style="color: <?= $mortalityColor ?>;"><?= number_format($mortality['rate'], 1) ?>%</div>
            <div class="text-muted small"><?= $mortality['deceased'] ?> of <?= $mortality['total'] ?> animals</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Total Income (6mo)</div>
            <div class="fs-4 fw-bold">
                <?= number_format(array_sum(array_column($analytics['revenueTrend'], 'income')), 0) ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Total Expenses (6mo)</div>
            <div class="fs-4 fw-bold">
                <?= number_format(array_sum(array_column($analytics['revenueTrend'], 'expense')), 0) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Revenue Growth: Income vs Expense (last 6 months)</h6>
                <canvas id="revenueTrendChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Worker Productivity (verified tasks)</h6>
                <?php if (empty($analytics['workerProductivity'])): ?>
                <p class="text-muted small mb-0">No worker task data yet.</p>
                <?php else: ?>
                <canvas id="workerProductivityChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Cost per Hectare by Crop Type</h6>
                <?php if (empty($analytics['costYieldPerHectare'])): ?>
                <p class="text-muted small mb-0">No crop cycle data with hectare-sized plots yet.</p>
                <?php else: ?>
                <canvas id="costPerHectareChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Yield per Hectare by Crop Type</h6>
                <?php if (empty($analytics['costYieldPerHectare'])): ?>
                <p class="text-muted small mb-0">No crop cycle data with hectare-sized plots yet.</p>
                <?php else: ?>
                <canvas id="yieldPerHectareChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const style = getComputedStyle(document.querySelector('.viz-root'));
    const colorIncome = style.getPropertyValue('--series-income').trim();
    const colorExpense = style.getPropertyValue('--series-expense').trim();
    const colorPrimary = style.getPropertyValue('--series-primary').trim();
    const colorGrid = style.getPropertyValue('--gridline').trim();
    const colorMuted = style.getPropertyValue('--muted').trim();

    Chart.defaults.font.family = "'IBM Plex Sans', system-ui, -apple-system, 'Segoe UI', sans-serif";
    Chart.defaults.color = colorMuted;
    Chart.defaults.borderColor = colorGrid;

    const revenueTrend = <?= json_encode($analytics['revenueTrend']) ?>;
    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'line',
        data: {
            labels: revenueTrend.map(r => r.month),
            datasets: [
                { label: 'Income', data: revenueTrend.map(r => r.income), borderColor: colorIncome, backgroundColor: colorIncome, borderWidth: 2, tension: 0.3, pointRadius: 3 },
                { label: 'Expense', data: revenueTrend.map(r => r.expense), borderColor: colorExpense, backgroundColor: colorExpense, borderWidth: 2, tension: 0.3, pointRadius: 3 }
            ]
        },
        options: {
            plugins: { legend: { display: true, position: 'bottom' } },
            scales: { y: { beginAtZero: true, grid: { color: colorGrid } }, x: { grid: { display: false } } }
        }
    });

    <?php if (!empty($analytics['workerProductivity'])): ?>
    const workerData = <?= json_encode($analytics['workerProductivity']) ?>;
    new Chart(document.getElementById('workerProductivityChart'), {
        type: 'bar',
        data: {
            labels: workerData.map(w => w.name),
            datasets: [{ label: 'Tasks Verified', data: workerData.map(w => parseInt(w.tasks_verified, 10)), backgroundColor: colorPrimary, borderRadius: 4, maxBarThickness: 28 }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid: { color: colorGrid }, ticks: { precision: 0 } }, y: { grid: { display: false } } }
        }
    });
    <?php endif; ?>

    <?php if (!empty($analytics['costYieldPerHectare'])): ?>
    const cyData = <?= json_encode($analytics['costYieldPerHectare']) ?>;
    new Chart(document.getElementById('costPerHectareChart'), {
        type: 'bar',
        data: {
            labels: cyData.map(c => c.crop_type_name),
            datasets: [{ label: 'Cost / Hectare', data: cyData.map(c => parseFloat(c.avg_cost_per_hectare)), backgroundColor: colorPrimary, borderRadius: 4, maxBarThickness: 40 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: colorGrid } }, x: { grid: { display: false } } } }
    });
    new Chart(document.getElementById('yieldPerHectareChart'), {
        type: 'bar',
        data: {
            labels: cyData.map(c => c.crop_type_name),
            datasets: [{ label: 'Yield / Hectare', data: cyData.map(c => parseFloat(c.avg_yield_per_hectare)), backgroundColor: colorPrimary, borderRadius: 4, maxBarThickness: 40 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: colorGrid } }, x: { grid: { display: false } } } }
    });
    <?php endif; ?>
})();
</script>
</div>
<?php endif; ?>
