<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT d.*, c.name AS country_name FROM destinations d JOIN countries c ON c.id = d.country_id WHERE d.id = ?');
$stmt->execute([$id]);
$destination = $stmt->fetch();

if (!$destination) {
    flash_set('error', 'That destination no longer exists.');
    redirect('/admin/destinations/index.php');
}

$animals = db()->prepare('SELECT * FROM destination_animals WHERE destination_id = ? ORDER BY name');
$animals->execute([$id]);
$animals = $animals->fetchAll();

$birds = db()->prepare('SELECT * FROM destination_birds WHERE destination_id = ? ORDER BY name');
$birds->execute([$id]);
$birds = $birds->fetchAll();

$activities = db()->prepare('SELECT * FROM destination_activities WHERE destination_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$galleryCount = media_count('destination', $id);

$page_title = $destination['name'];
$page_eyebrow = 'Safari · ' . $destination['country_name'];
$active_nav = 'destinations';
$page_action_html = '<a class="btn btn--ghost" href="' . h(url('/admin/destinations/form.php?id=' . $id)) . '">Edit details</a>';

require __DIR__ . '/../includes/header.php';

$returnUrl = $_SERVER['REQUEST_URI'];
?>

<div class="panel">
  <div class="panel__body">
    <p style="margin:0; opacity:0.75; font-size:13.5px;"><?= nl2br(h($destination['overview'] ?: 'No overview yet.')) ?></p>
  </div>
</div>

<div class="panel">
  <div class="panel__header"><div class="panel__title">Animals found here</div></div>
  <div class="panel__body">
    <?php if ($animals): ?>
      <div class="tag-list" style="margin-bottom:14px;">
        <?php foreach ($animals as $animal): ?>
          <span class="tag">
            <?= h($animal['name']) ?>
            <form method="post" action="<?= h(url('/admin/destinations/tags.php?action=delete')) ?>" style="display:contents;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $animal['id'] ?>">
              <input type="hidden" name="kind" value="animal">
              <input type="hidden" name="destination_id" value="<?= $id ?>">
              <button type="submit" title="Remove">&times;</button>
            </form>
          </span>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="section-hint">No animals added yet.</p>
    <?php endif; ?>
    <form method="post" action="<?= h(url('/admin/destinations/tags.php?action=add')) ?>" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="kind" value="animal">
      <input type="hidden" name="destination_id" value="<?= $id ?>">
      <input type="text" name="name" placeholder="e.g. Mountain Gorilla" required>
      <button type="submit" class="btn btn--ghost btn--sm">Add animal</button>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel__header"><div class="panel__title">Birds found here</div></div>
  <div class="panel__body">
    <?php if ($birds): ?>
      <div class="tag-list" style="margin-bottom:14px;">
        <?php foreach ($birds as $bird): ?>
          <span class="tag">
            <?= h($bird['name']) ?>
            <form method="post" action="<?= h(url('/admin/destinations/tags.php?action=delete')) ?>" style="display:contents;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $bird['id'] ?>">
              <input type="hidden" name="kind" value="bird">
              <input type="hidden" name="destination_id" value="<?= $id ?>">
              <button type="submit" title="Remove">&times;</button>
            </form>
          </span>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="section-hint">No birds added yet.</p>
    <?php endif; ?>
    <form method="post" action="<?= h(url('/admin/destinations/tags.php?action=add')) ?>" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="kind" value="bird">
      <input type="hidden" name="destination_id" value="<?= $id ?>">
      <input type="text" name="name" placeholder="e.g. Great Blue Turaco" required>
      <button type="submit" class="btn btn--ghost btn--sm">Add bird</button>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Popular activities</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/destinations/activities/form.php?destination_id=' . $id)) ?>">Add activity</a>
  </div>
  <div class="panel__body">
    <?php if (!$activities): ?>
      <div class="empty-state">
        <div class="empty-state__title">No popular activities yet</div>
        <div class="empty-state__body">These show as tiles with a gallery on the destination page.</div>
      </div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($activities as $activity):
          $activityGalleryCount = media_count('destination_activity', $activity['id']);
        ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title"><?= h($activity['title']) ?></div>
              <div class="sub-row__meta"><?= h(mb_strimwidth($activity['description'] ?? '', 0, 120, '…')) ?></div>
              <div class="sub-row__meta"><?= $activityGalleryCount ?> gallery image<?= $activityGalleryCount === 1 ? '' : 's' ?></div>
            </div>
            <div class="table__actions">
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=destination_activity&entity_id=' . $activity['id'] . '&title=' . urlencode($activity['title'] . ' gallery') . '&back=' . urlencode($returnUrl))) ?>">Gallery</a>
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/destinations/activities/form.php?destination_id=' . $id . '&id=' . $activity['id'])) ?>">Edit</a>
              <form method="post" action="<?= h(url('/admin/destinations/activities/delete.php')) ?>" onsubmit="return confirm('Remove this activity?');">
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
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=destination&entity_id=' . $id . '&title=' . urlencode($destination['name'] . ' gallery') . '&back=' . urlencode($returnUrl))) ?>">Manage gallery (<?= $galleryCount ?>)</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
