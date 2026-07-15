<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-90 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// --- Animals by species ---
$speciesRows = db_all(
    "SELECT species AS label, COUNT(*) AS total FROM animals WHERE organization_id = :id GROUP BY species ORDER BY total DESC",
    ['id' => $orgId]
);
$speciesChart = [
    'labels' => array_map(fn ($r) => humanize((string) $r['label']), $speciesRows),
    'data' => array_map(fn ($r) => (int) $r['total'], $speciesRows),
];

// --- Animals by status ---
$statusRows = db_all(
    "SELECT status AS label, COUNT(*) AS total FROM animals WHERE organization_id = :id GROUP BY status ORDER BY total DESC",
    ['id' => $orgId]
);
$statusChart = [
    'labels' => array_map(fn ($r) => humanize((string) $r['label']), $statusRows),
    'data' => array_map(fn ($r) => (int) $r['total'], $statusRows),
];
$totalAnimals = array_sum($statusChart['data']);

// --- Animal events breakdown by event_type, within period ---
$eventBreakdown = db_all(
    "SELECT ae.event_type AS label, COUNT(*) AS total
     FROM animal_events ae
     JOIN animals a ON a.id = ae.animal_id
     WHERE a.organization_id = :id AND ae.event_date BETWEEN :date_from AND :date_to
     GROUP BY ae.event_type ORDER BY total DESC",
    ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
);

// --- Recent production events ---
$productionEvents = db_all(
    "SELECT ae.*, a.name AS animal_name, a.animal_id AS animal_tag, a.species
     FROM animal_events ae
     JOIN animals a ON a.id = ae.animal_id
     WHERE a.organization_id = :id AND ae.event_type = 'production' AND ae.event_date BETWEEN :date_from AND :date_to
     ORDER BY ae.event_date DESC LIMIT 25",
    ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
);

$exportParams = http_build_query(['report' => 'livestock', 'date_from' => $dateFrom, 'date_to' => $dateTo]);

render_header(['title' => 'Livestock Report', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Livestock Report</h4>
          <p class="text-muted small mb-0">Herd composition and production activity.</p>
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
            <a href="<?= base_url('org-admin/reporting/livestock.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <?php render_stat_card('Total Animals', (string) $totalAnimals, null, 'bi-egg-fried', 'primary'); ?>
        </div>
        <div class="col-sm-4">
          <?php render_stat_card('Species Tracked', (string) count($speciesChart['labels']), null, 'bi-collection', 'info'); ?>
        </div>
        <div class="col-sm-4">
          <?php render_stat_card('Production Events (period)', (string) count($productionEvents), null, 'bi-graph-up', 'secondary'); ?>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Animals by Species</h6></div>
            <?php if ($speciesChart['data']): ?>
              <canvas id="speciesChart" height="140"></canvas>
            <?php else: ?>
              <?php render_empty_state('No animals recorded yet.', 'bi-egg-fried'); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="content-card-header"><h6>Animals by Status</h6></div>
            <?php if ($statusChart['data']): ?>
              <canvas id="statusChart" height="140"></canvas>
            <?php else: ?>
              <?php render_empty_state('No animals recorded yet.', 'bi-egg-fried'); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="content-card mb-4">
        <div class="content-card-header"><h6>Event Type Breakdown (selected period)</h6></div>
        <?php if (!$eventBreakdown): ?>
          <?php render_empty_state('No animal events recorded in this period.', 'bi-clipboard-pulse'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead><tr><th>Event Type</th><th class="text-end">Count</th></tr></thead>
              <tbody>
                <?php foreach ($eventBreakdown as $e): ?>
                  <tr><td><?= e(humanize($e['label'])) ?></td><td class="text-end"><?= (int) $e['total'] ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Recent Production Events</h6></div>
        <?php if (!$productionEvents): ?>
          <?php render_empty_state('No production events recorded in this period.', 'bi-graph-up'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Animal</th>
                  <th>Species</th>
                  <th>Details</th>
                  <th class="text-end">Cost</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($productionEvents as $pe): ?>
                  <tr>
                    <td><?= format_date($pe['event_date']) ?></td>
                    <td><?= e($pe['animal_name'] ?: $pe['animal_tag']) ?></td>
                    <td><?= e(humanize($pe['species'])) ?></td>
                    <td><?= e($pe['details'] ?: '-') ?></td>
                    <td class="text-end"><?= $pe['cost'] !== null ? format_money((float) $pe['cost']) : '-' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
<?php if ($speciesChart['data']): ?>
  renderDoughnutChart('speciesChart', <?= json_encode($speciesChart['labels']) ?>, <?= json_encode($speciesChart['data']) ?>);
<?php endif; ?>
<?php if ($statusChart['data']): ?>
  renderDoughnutChart('statusChart', <?= json_encode($statusChart['labels']) ?>, <?= json_encode($statusChart['data']) ?>);
<?php endif; ?>
});
</script>
<?php render_footer(['context' => 'org-admin', 'js' => ['charts']]); ?>
