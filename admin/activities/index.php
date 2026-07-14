<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Activities';
$page_eyebrow = 'Activities';
$active_nav = 'activities';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/activities/form.php')) . '">Add activity</a>';

$activities = db()->query("SELECT a.*, d.name AS destination_name,
        (SELECT COUNT(*) FROM tour_activities ta WHERE ta.activity_id = a.id) AS tour_count,
        (SELECT COUNT(*) FROM destination_activities da WHERE da.activity_id = a.id) AS destination_activity_count
    FROM activities a
    LEFT JOIN destinations d ON d.id = a.destination_id
    ORDER BY a.name")->fetchAll();

$activitiesInUse = 0;
foreach ($activities as $activity) {
    if ((int) $activity['tour_count'] > 0 || (int) $activity['destination_activity_count'] > 0) {
        $activitiesInUse++;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($activities): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('sliders') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Activities</div><div class="stat-tile__value"><?= count($activities) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">In use</div><div class="stat-tile__value"><?= $activitiesInUse ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Unused</div><div class="stat-tile__value"><?= count($activities) - $activitiesInUse ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$activities): ?>
    <div class="empty-state">
      <div class="empty-state__title">No activities yet</div>
      <div class="empty-state__body">These populate the Activities menu (Skydiving, White Water Rafting, and so on).</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/activities/form.php')) ?>">Add the first activity</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Destination</th>
          <th>Duration</th>
          <th>Used in</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($activities as $activity): ?>
          <tr>
            <td><?= h($activity['name']) ?></td>
            <td class="table__meta"><?= h($activity['destination_name'] ?? '—') ?></td>
            <td class="table__meta"><?= $activity['duration_hours'] !== null ? h((string) $activity['duration_hours']) . ' hrs' : '—' ?></td>
            <td class="table__meta"><?= (int) $activity['tour_count'] ?> tour<?= (int) $activity['tour_count'] === 1 ? '' : 's' ?>, <?= (int) $activity['destination_activity_count'] ?> destination page<?= (int) $activity['destination_activity_count'] === 1 ? '' : 's' ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/activities/manage.php?id=' . $activity['id'])) ?>">Manage</a>
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/activities/form.php?id=' . $activity['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/activities/delete.php')) ?>" onsubmit="return confirm('Delete this activity?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $activity['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
