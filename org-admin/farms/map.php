<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$farms = tenant_all('farms', $orgId, 'AND gps_latitude IS NOT NULL AND gps_longitude IS NOT NULL ORDER BY name');

$blocks = db_all(
    'SELECT b.*, f.name AS farm_name FROM blocks b
     JOIN farms f ON f.id = b.farm_id
     WHERE f.organization_id = :org_id AND b.gps_latitude IS NOT NULL AND b.gps_longitude IS NOT NULL
     ORDER BY b.name',
    ['org_id' => $orgId]
);

$plots = db_all(
    'SELECT p.*, bl.name AS block_name, f.name AS farm_name FROM plots p
     JOIN blocks bl ON bl.id = p.block_id
     JOIN farms f ON f.id = bl.farm_id
     WHERE f.organization_id = :org_id AND p.gps_latitude IS NOT NULL AND p.gps_longitude IS NOT NULL
     ORDER BY p.name',
    ['org_id' => $orgId]
);

$points = [];
foreach ($farms as $farm) {
    $points[] = [
        'lat' => (float) $farm['gps_latitude'],
        'lng' => (float) $farm['gps_longitude'],
        'label' => '<strong>' . e($farm['name']) . '</strong><br>Farm<br><a href="' . base_url('org-admin/farms/blocks.php?farm_id=' . $farm['id']) . '">View blocks</a>',
    ];
}
foreach ($blocks as $block) {
    $points[] = [
        'lat' => (float) $block['gps_latitude'],
        'lng' => (float) $block['gps_longitude'],
        'label' => '<strong>' . e($block['name']) . '</strong><br>Block of ' . e($block['farm_name']) . '<br><a href="' . base_url('org-admin/farms/plots.php?block_id=' . $block['id']) . '">View plots</a>',
    ];
}
foreach ($plots as $plot) {
    $points[] = [
        'lat' => (float) $plot['gps_latitude'],
        'lng' => (float) $plot['gps_longitude'],
        'label' => '<strong>' . e($plot['name']) . '</strong><br>Plot in ' . e($plot['block_name']) . ', ' . e($plot['farm_name']),
    ];
}

$totalPlaced = count($points);
$totalStructures = count($farms) + count($blocks) + count($plots);

render_header(['title' => 'Farm Map', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Farm Map</h4>
          <p class="text-muted small mb-0"><?= $totalPlaced ?> of <?= $totalStructures ?> farms/blocks/plots have GPS coordinates recorded.</p>
        </div>
        <a href="<?= base_url('org-admin/farms/index.php') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list"></i> List View</a>
      </div>

      <?php if (!$points): ?>
        <div class="content-card">
          <?php render_empty_state('No farms, blocks, or plots have GPS coordinates yet. Add coordinates when creating or editing a farm to see it here.', 'bi-map'); ?>
        </div>
      <?php else: ?>
        <div class="content-card">
          <div id="farmsMap" style="height:560px;border-radius:var(--sfp-radius);"></div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php if ($points): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    initMapView('farmsMap', <?= json_encode($points, JSON_UNESCAPED_SLASHES) ?>);
});
</script>
<?php endif; ?>
<?php render_footer(['context' => 'org-admin', 'js' => ['map']]); ?>
