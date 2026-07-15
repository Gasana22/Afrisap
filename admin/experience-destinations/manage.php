<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT ed.*, et.name AS type_name FROM experience_destinations ed JOIN experience_types et ON et.id = ed.experience_type_id WHERE ed.id = ?');
$stmt->execute([$id]);
$destination = $stmt->fetch();

if (!$destination) {
    flash_set('error', 'That destination no longer exists.');
    redirect('/admin/experience-destinations/index.php');
}

$activities = db()->prepare('SELECT * FROM experience_destination_activities WHERE experience_destination_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$galleryCount = media_count('experience_destination', $id);

$page_title = $destination['name'];
$page_eyebrow = 'Experiential · ' . $destination['type_name'];
$active_nav = 'experience-destinations';
$page_action_html = '<a class="btn btn--ghost" href="' . h(url('/admin/experience-destinations/form.php?id=' . $id)) . '">Edit details</a>';

require __DIR__ . '/../includes/header.php';
$returnUrl = $_SERVER['REQUEST_URI'];
?>

<div class="panel">
  <div class="panel__body">
    <p style="margin:0; opacity:0.75; font-size:13.5px;"><?= nl2br(h($destination['short_overview'] ?: 'No overview yet.')) ?></p>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Activities &amp; cultural uniqueness</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/experience-destinations/activities/form.php?destination_id=' . $id)) ?>">Add activity</a>
  </div>
  <div class="panel__body">
    <?php if (!$activities): ?>
      <div class="empty-state">
        <div class="empty-state__title">No activities yet</div>
        <div class="empty-state__body">Shown as tile + description boxes, without a day-by-day breakdown.</div>
      </div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($activities as $activity): $g = media_count('experience_destination_activity', $activity['id']); ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title"><?= h($activity['title']) ?></div>
              <div class="sub-row__meta"><?= h(mb_strimwidth($activity['description'] ?? '', 0, 120, '…')) ?></div>
              <div class="sub-row__meta"><?= $g ?> gallery image<?= $g === 1 ? '' : 's' ?></div>
            </div>
            <div class="table__actions">
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=experience_destination_activity&entity_id=' . $activity['id'] . '&title=' . urlencode($activity['title'] . ' gallery') . '&back=' . urlencode($returnUrl))) ?>">Gallery</a>
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-destinations/activities/form.php?destination_id=' . $id . '&id=' . $activity['id'])) ?>">Edit</a>
              <form method="post" action="<?= h(url('/admin/experience-destinations/activities/delete.php')) ?>" onsubmit="return confirm('Remove this activity?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $activity['id'] ?>">
                <input type="hidden" name="destination_id" value="<?= $id ?>">
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Gallery</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=experience_destination&entity_id=' . $id . '&title=' . urlencode($destination['name'] . ' gallery') . '&back=' . urlencode($returnUrl))) ?>">Manage gallery (<?= $galleryCount ?>)</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
