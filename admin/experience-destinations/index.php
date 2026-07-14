<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Experience Destinations';
$page_eyebrow = 'Experiential';
$active_nav = 'experience-destinations';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/experience-destinations/form.php')) . '">Add destination</a>';

$destinations = db()->query("SELECT ed.*, et.name AS type_name,
        (SELECT COUNT(*) FROM experience_destination_activities eda WHERE eda.experience_destination_id = ed.id) AS activity_count
    FROM experience_destinations ed
    JOIN experience_types et ON et.id = ed.experience_type_id
    ORDER BY et.name, ed.name")->fetchAll();

$grouped = [];
foreach ($destinations as $row) {
    $grouped[$row['type_name']][] = $row;
}

$totalActivities = array_sum(array_column($destinations, 'activity_count'));

require __DIR__ . '/../includes/header.php';
?>

<?php if ($destinations): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('pin') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Experience destinations</div><div class="stat-tile__value"><?= count($destinations) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('mask') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Experience types</div><div class="stat-tile__value"><?= count($grouped) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('sliders') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Activities linked</div><div class="stat-tile__value"><?= $totalActivities ?></div></div>
  </div>
</div>
<?php endif; ?>

<?php if (!$destinations): ?>
  <div class="panel">
    <div class="empty-state">
      <div class="empty-state__title">No experience destinations yet</div>
      <div class="empty-state__body">Tribes, farm types, sports, industries and ghetto areas all live here.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/experience-destinations/form.php')) ?>">Add the first one</a>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($grouped as $typeName => $rows): ?>
    <div class="panel">
      <div class="panel__header"><div class="panel__title"><?= h($typeName) ?></div></div>
      <table class="table">
        <thead>
          <tr><th>Name</th><th>Location</th><th>Activities</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= h($row['name']) ?></td>
              <td class="table__meta"><?= h($row['location'] ?: '—') ?></td>
              <td class="table__meta"><?= (int) $row['activity_count'] ?></td>
              <td>
                <div class="table__actions">
                  <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-destinations/manage.php?id=' . $row['id'])) ?>">Manage</a>
                  <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-destinations/form.php?id=' . $row['id'])) ?>">Edit</a>
                  <form method="post" action="<?= h(url('/admin/experience-destinations/delete.php')) ?>" onsubmit="return confirm('Delete this destination?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
