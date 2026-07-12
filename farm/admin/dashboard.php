<?php
require_once __DIR__ . '/includes/auth-check.php';

if (is_platform_user()) {
    $farmCount = (int) db()->query('SELECT COUNT(*) FROM farms')->fetchColumn();
    $orgCount  = (int) db()->query('SELECT COUNT(*) FROM organizations')->fetchColumn();
} else {
    $stmt = db()->prepare('SELECT COUNT(*) FROM farms WHERE organization_id = :org_id');
    $stmt->execute(['org_id' => current_organization_id()]);
    $farmCount = (int) $stmt->fetchColumn();
    $orgCount = 1;
}

$farmIds = visible_farm_ids();
$activeCropCycles = $activeAnimals = $activeWorkers = $pendingTasks = 0;

if ($farmIds) {
    $cycleStmt = db()->prepare(
        "SELECT COUNT(*) FROM crop_cycles cc JOIN plots p ON p.id = cc.plot_id JOIN blocks b ON b.id = p.block_id
         WHERE b.farm_id IN (" . in_placeholders($farmIds) . ") AND cc.status NOT IN ('harvested', 'closed')"
    );
    $cycleStmt->execute($farmIds);
    $activeCropCycles = (int) $cycleStmt->fetchColumn();

    $animalStmt = db()->prepare('SELECT COUNT(*) FROM animals WHERE farm_id IN (' . in_placeholders($farmIds) . ') AND status = "active"');
    $animalStmt->execute($farmIds);
    $activeAnimals = (int) $animalStmt->fetchColumn();

    $workerStmt = db()->prepare('SELECT COUNT(*) FROM workers WHERE farm_id IN (' . in_placeholders($farmIds) . ') AND status = "active"');
    $workerStmt->execute($farmIds);
    $activeWorkers = (int) $workerStmt->fetchColumn();

    $taskStmt = db()->prepare(
        'SELECT COUNT(*) FROM worker_tasks wt JOIN workers w ON w.id = wt.worker_id
         WHERE w.farm_id IN (' . in_placeholders($farmIds) . ") AND wt.status IN ('pending', 'ongoing')"
    );
    $taskStmt->execute($farmIds);
    $pendingTasks = (int) $taskStmt->fetchColumn();
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<h1>Welcome, <?= e(current_user()['name'] ?? '') ?></h1>

<div class="stat-grid">
    <div class="card">
        <div class="number"><?= $farmCount ?></div>
        <div>Farms</div>
    </div>
    <div class="card">
        <div class="number"><?= $orgCount ?></div>
        <div>Organization(s)</div>
    </div>
    <div class="card">
        <div class="number"><?= $activeCropCycles ?></div>
        <div>Active crop cycles</div>
    </div>
    <div class="card">
        <div class="number"><?= $activeAnimals ?></div>
        <div>Active animals</div>
    </div>
    <div class="card">
        <div class="number"><?= $activeWorkers ?></div>
        <div>Active workers</div>
    </div>
    <div class="card">
        <div class="number"><?= $pendingTasks ?></div>
        <div>Pending worker tasks</div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
