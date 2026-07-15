<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$animalId = clean_int($_GET['id'] ?? 0);
$animal = tenant_find('animals', $orgId, $animalId);

if (!$animal) {
    session_flash('error', 'Animal not found.');
    redirect('org-admin/livestock/index.php');
}

$farm = tenant_find('farms', $orgId, (int) $animal['farm_id']);

$parentIds = json_decode($animal['parent_ids'] ?? '[]', true) ?: [];
$parents = [];
if ($parentIds) {
    $placeholders = [];
    $params = ['organization_id' => $orgId];
    foreach (array_values($parentIds) as $i => $pid) {
        $key = "pid$i";
        $placeholders[] = ":$key";
        $params[$key] = $pid;
    }
    $parents = db_all(
        'SELECT id, animal_id, name FROM animals WHERE organization_id = :organization_id AND animal_id IN (' . implode(',', $placeholders) . ')',
        $params
    );
}

$events = db_all(
    'SELECT e.*, w.name AS handler_name FROM animal_events e LEFT JOIN workers w ON w.id = e.handler_id WHERE e.animal_id = :animal_id ORDER BY e.event_date DESC, e.id DESC',
    ['animal_id' => $animalId]
);

$movements = db_all(
    'SELECT m.*, ff.name AS from_farm_name, tf.name AS to_farm_name FROM animal_movements m LEFT JOIN farms ff ON ff.id = m.from_farm_id LEFT JOIN farms tf ON tf.id = m.to_farm_id WHERE m.animal_id = :animal_id ORDER BY m.movement_date DESC, m.id DESC',
    ['animal_id' => $animalId]
);

$eventIcons = [
    'birth' => 'bi-flag',
    'vaccination' => 'bi-shield-plus',
    'feeding' => 'bi-basket',
    'weight' => 'bi-speedometer2',
    'breeding' => 'bi-heart',
    'production' => 'bi-egg-fried',
    'treatment' => 'bi-bandaid',
    'death' => 'bi-x-octagon',
    'sale' => 'bi-cash-coin',
];

$timeline = [];
foreach ($events as $ev) {
    $timeline[] = [
        'sort_key' => $ev['event_date'] . ' ' . str_pad((string) $ev['id'], 10, '0', STR_PAD_LEFT),
        'date' => $ev['event_date'],
        'icon' => $eventIcons[$ev['event_type']] ?? 'bi-calendar-event',
        'label' => humanize($ev['event_type']),
        'details' => $ev['details'],
        'cost' => $ev['cost'],
        'meta' => $ev['handler_name'] ? 'Handled by ' . $ev['handler_name'] : null,
    ];
}
foreach ($movements as $mv) {
    $desc = ($mv['from_farm_name'] ?: 'Unknown farm') . ' &rarr; ' . ($mv['to_farm_name'] ?: 'Unknown farm');
    $timeline[] = [
        'sort_key' => $mv['movement_date'] . ' ' . str_pad((string) $mv['id'], 10, '0', STR_PAD_LEFT),
        'date' => $mv['movement_date'],
        'icon' => 'bi-truck',
        'label' => 'Movement',
        'details' => $desc . ($mv['reason'] ? ' - ' . $mv['reason'] : '') . ($mv['notes'] ? ' (' . $mv['notes'] . ')' : ''),
        'cost' => null,
        'meta' => null,
    ];
}
usort($timeline, fn ($a, $b) => strcmp($b['sort_key'], $a['sort_key']));

render_header(['title' => 'Animal - ' . ($animal['name'] ?: $animal['animal_id']), 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/livestock/index.php') ?>">Livestock</a></li>
        <li class="breadcrumb-item active"><?= e($animal['name'] ?: $animal['animal_id']) ?></li>
      </ol></nav>

      <div class="content-card mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <h4 class="mb-1"><?= e($animal['name'] ?: $animal['animal_id']) ?> <?php render_status_badge($animal['status']); ?></h4>
            <p class="text-muted mb-0"><?= e($animal['animal_id']) ?><?= $animal['tag_number'] ? ' &middot; Tag ' . e($animal['tag_number']) : '' ?></p>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= base_url('org-admin/livestock/events.php?animal_id=' . $animal['id']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus"></i> Log Event</a>
            <a href="<?= base_url('org-admin/livestock/movements.php?animal_id=' . $animal['id']) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-truck"></i> Log Movement</a>
          </div>
        </div>
        <hr>
        <div class="row g-3">
          <div class="col-md-3 col-6">
            <div class="text-muted small">Species / Breed</div>
            <div class="fw-semibold"><?= e(humanize($animal['species'])) ?><?= $animal['breed'] ? ' / ' . e($animal['breed']) : '' ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Gender</div>
            <div class="fw-semibold"><?= e(humanize($animal['gender'])) ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Birth Date</div>
            <div class="fw-semibold"><?= e(format_date($animal['birth_date'])) ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Age</div>
            <div class="fw-semibold"><?= e(calculate_age($animal['birth_date'])) ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Farm</div>
            <div class="fw-semibold"><?= e($farm['name'] ?? '-') ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Purchase Price</div>
            <div class="fw-semibold"><?= $animal['purchase_price'] !== null ? format_money((float) $animal['purchase_price']) : '-' ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Sale Price</div>
            <div class="fw-semibold"><?= $animal['sale_price'] !== null ? format_money((float) $animal['sale_price']) : '-' ?></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="text-muted small">Parents</div>
            <div class="fw-semibold">
              <?php if (!$parents): ?>
                -
              <?php else: ?>
                <?php foreach ($parents as $p): ?>
                  <a href="<?= base_url('org-admin/livestock/view.php?id=' . $p['id']) ?>"><?= e($p['name'] ?: $p['animal_id']) ?></a><?= $p !== end($parents) ? ', ' : '' ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="content-card">
        <h6 class="mb-3">Timeline</h6>
        <?php if (!$timeline): ?>
          <?php render_empty_state('No events or movements recorded yet.', 'bi-clock-history'); ?>
        <?php else: ?>
          <div class="timeline">
            <?php foreach ($timeline as $item): ?>
              <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <div><i class="bi <?= e($item['icon']) ?> fs-5 text-primary"></i></div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between">
                    <strong><?= e($item['label']) ?></strong>
                    <span class="text-muted small"><?= e(format_date($item['date'])) ?></span>
                  </div>
                  <?php if ($item['details']): ?><div class="small"><?= nl2br(e($item['details'])) ?></div><?php endif; ?>
                  <?php if ($item['cost'] !== null): ?><div class="small text-muted">Cost: <?= format_money((float) $item['cost']) ?></div><?php endif; ?>
                  <?php if ($item['meta']): ?><div class="small text-muted"><?= e($item['meta']) ?></div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
