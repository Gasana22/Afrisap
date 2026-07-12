<?php
require_once __DIR__ . '/includes/auth-check.php';

// Pure reads, tenant-scoped via visible_farm_ids() -- no permission gate
// needed beyond being logged in, same as the rest of the read side of the
// admin panel (see CLAUDE.md's "reads stay open" convention).

$farmIds = visible_farm_ids();
$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? '';

$reportTypes = [
    'crop-yield' => 'Crop Yield',
    'livestock-production' => 'Livestock Production',
    'worker-productivity' => 'Worker Productivity',
    'daily-activities' => 'Daily Activities',
];

if ($type === '' || !isset($reportTypes[$type]) || !$farmIds) {
    $pageTitle = 'Reports';
    $activePage = 'reports';
    require __DIR__ . '/includes/header.php';
    ?>
    <h1>Reports</h1>
    <?php if (!$farmIds): ?>
        <div class="alert alert-error">No farms available yet.</div>
    <?php else: ?>
        <table>
            <thead><tr><th>Report</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($reportTypes as $slug => $label): ?>
                    <tr><td><?= e($label) ?></td><td><a href="<?= BASE_URL ?>/admin/reports.php?type=<?= $slug ?>">View</a></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php
    exit;
}

$title = $reportTypes[$type];
$placeholders = in_placeholders($farmIds);

if ($type === 'crop-yield') {
    $stmt = db()->prepare(
        "SELECT cc.batch_code, ct.name AS crop_type, f.name AS farm_name, p.plot_code, p.size_hectares,
                COALESCE(SUM(h.quantity), 0) AS total_yield, cc.status
         FROM crop_cycles cc
         JOIN crop_types ct ON ct.id = cc.crop_type_id
         JOIN plots p ON p.id = cc.plot_id
         JOIN blocks b ON b.id = p.block_id
         JOIN farms f ON f.id = b.farm_id
         LEFT JOIN harvests h ON h.crop_cycle_id = cc.id
         WHERE f.id IN ($placeholders)
         GROUP BY cc.id, cc.batch_code, ct.name, f.name, p.plot_code, p.size_hectares, cc.status
         ORDER BY cc.batch_code"
    );
    $stmt->execute($farmIds);
    $rows = $stmt->fetchAll();
    $headers = ['Batch Code', 'Crop Type', 'Farm', 'Plot', 'Hectares', 'Total Yield', 'Yield/Hectare', 'Status'];
    $tableRows = array_map(function ($r) {
        $perHectare = $r['size_hectares'] > 0 ? round($r['total_yield'] / $r['size_hectares'], 2) : 0;
        return [$r['batch_code'], $r['crop_type'], $r['farm_name'], $r['plot_code'], $r['size_hectares'], $r['total_yield'], $perHectare, $r['status']];
    }, $rows);
} elseif ($type === 'livestock-production') {
    $stmt = db()->prepare(
        "SELECT a.animal_code, a.species, a.name, f.name AS farm_name, a.status, COALESCE(SUM(ap.quantity), 0) AS total_production
         FROM animals a JOIN farms f ON f.id = a.farm_id LEFT JOIN animal_production ap ON ap.animal_id = a.id
         WHERE f.id IN ($placeholders)
         GROUP BY a.id, a.animal_code, a.species, a.name, f.name, a.status
         ORDER BY a.animal_code"
    );
    $stmt->execute($farmIds);
    $rows = $stmt->fetchAll();
    $headers = ['Animal ID', 'Species', 'Name', 'Farm', 'Total Production', 'Status'];
    $tableRows = array_map(fn ($r) => [$r['animal_code'], $r['species'], $r['name'] ?? '', $r['farm_name'], $r['total_production'], $r['status']], $rows);
} elseif ($type === 'worker-productivity') {
    $stmt = db()->prepare(
        "SELECT w.name, f.name AS farm_name,
                (SELECT COUNT(*) FROM worker_tasks wt WHERE wt.worker_id = w.id AND wt.status = 'verified') AS tasks_verified,
                (SELECT COUNT(*) FROM worker_attendance wa WHERE wa.worker_id = w.id AND wa.status = 'present') AS days_present
         FROM workers w JOIN farms f ON f.id = w.farm_id
         WHERE f.id IN ($placeholders)
         ORDER BY w.name"
    );
    $stmt->execute($farmIds);
    $rows = $stmt->fetchAll();
    $headers = ['Worker', 'Farm', 'Tasks Verified', 'Days Present'];
    $tableRows = array_map(fn ($r) => [$r['name'], $r['farm_name'], $r['tasks_verified'], $r['days_present']], $rows);
} else { // daily-activities
    $from = ($_GET['from'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
    $to = ($_GET['to'] ?? '') ?: date('Y-m-d');
    $stmt = db()->prepare(
        "SELECT fa.activity_date, fa.activity_type, fa.worker_name, f.name AS farm_name, cc.batch_code, fa.status, fa.cost
         FROM field_activities fa
         JOIN crop_cycles cc ON cc.id = fa.crop_cycle_id
         JOIN plots p ON p.id = cc.plot_id JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
         WHERE f.id IN ($placeholders) AND fa.activity_date BETWEEN ? AND ?
         ORDER BY fa.activity_date DESC"
    );
    $stmt->execute(array_merge($farmIds, [$from, $to]));
    $rows = $stmt->fetchAll();
    $headers = ['Date', 'Activity', 'Worker', 'Farm', 'Batch', 'Status', 'Cost'];
    $tableRows = array_map(fn ($r) => [$r['activity_date'], ucfirst($r['activity_type']), $r['worker_name'] ?? '', $r['farm_name'], $r['batch_code'], ucfirst($r['status']), $r['cost'] ?? ''], $rows);
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ',', '"', '\\');
    foreach ($tableRows as $row) {
        fputcsv($out, $row, ',', '"', '\\');
    }
    fclose($out);
    exit;
}

if ($format === 'print') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= e($title) ?> — <?= e(APP_NAME) ?></title>
        <style>
            body { font-family: system-ui, sans-serif; font-size: 0.85rem; color: #222; padding: 1.5rem; }
            h1 { font-size: 1.2rem; margin-bottom: 0.2rem; }
            .meta { color: #666; font-size: 0.8rem; margin-bottom: 1rem; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 0.4rem 0.6rem; text-align: left; }
            th { background: #f0f0f0; }
            .print-actions { margin-bottom: 1rem; }
            @media print { .print-actions { display: none; } }
        </style>
    </head>
    <body>
        <div class="print-actions"><button onclick="window.print()">Print / Save as PDF</button></div>
        <h1><?= e($title) ?></h1>
        <div class="meta">Generated <?= e(date('Y-m-d H:i')) ?></div>
        <table>
            <thead><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
                <?php if (!$tableRows): ?><tr><td colspan="<?= count($headers) ?>">No data.</td></tr><?php endif; ?>
                <?php foreach ($tableRows as $row): ?>
                    <tr><?php foreach ($row as $cell): ?><td><?= e((string) $cell) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

$pageTitle = $title;
$activePage = 'reports';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/reports.php">&larr; All reports</a></p>
<h1><?= e($title) ?></h1>

<?php if ($type === 'daily-activities'): ?>
<form method="GET" action="<?= BASE_URL ?>/admin/reports.php" style="display:flex; gap:1rem; align-items:flex-end; margin-bottom:1rem;">
    <input type="hidden" name="type" value="daily-activities">
    <div><label>From</label><input type="date" name="from" value="<?= e($from) ?>" style="padding:0.4rem;"></div>
    <div><label>To</label><input type="date" name="to" value="<?= e($to) ?>" style="padding:0.4rem;"></div>
    <button type="submit" class="btn">Filter</button>
</form>
<?php endif; ?>

<p>
    <a class="btn" href="<?= BASE_URL ?>/admin/reports.php?<?= http_build_query(array_merge($_GET, ['format' => 'csv'])) ?>">Download CSV</a>
    <a class="btn btn-outline" href="<?= BASE_URL ?>/admin/reports.php?<?= http_build_query(array_merge($_GET, ['format' => 'print'])) ?>" target="_blank">Print / Save as PDF</a>
</p>

<table>
    <thead><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
        <?php if (!$tableRows): ?><tr><td colspan="<?= count($headers) ?>">No data.</td></tr><?php endif; ?>
        <?php foreach ($tableRows as $row): ?>
            <tr><?php foreach ($row as $cell): ?><td><?= e((string) $cell) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
