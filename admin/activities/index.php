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

require __DIR__ . '/../includes/header.php';
?>

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
          <th></th>
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
            <td style="width:52px;">
              <?php if ($activity['image_path']): ?>
                <img src="<?= h(url('/' . $activity['image_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid var(--line);">
              <?php endif; ?>
            </td>
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
