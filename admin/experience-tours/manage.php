<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT et.*, ety.name AS type_name FROM experience_tours et JOIN experience_types ety ON ety.id = et.experience_type_id WHERE et.id = ?');
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) {
    flash_set('error', 'That tour no longer exists.');
    redirect('/admin/experience-tours/index.php');
}

$typeDestinations = db()->prepare('SELECT id, name, location FROM experience_destinations WHERE experience_type_id = ? ORDER BY name');
$typeDestinations->execute([$tour['experience_type_id']]);
$typeDestinations = $typeDestinations->fetchAll();

$selectedStmt = db()->prepare('SELECT experience_destination_id FROM experience_tour_destinations WHERE experience_tour_id = ?');
$selectedStmt->execute([$id]);
$selectedIds = array_map('intval', array_column($selectedStmt->fetchAll(), 'experience_destination_id'));

$activities = db()->prepare('SELECT * FROM experience_tour_activities WHERE experience_tour_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$itineraryDays = db()->prepare('SELECT * FROM experience_tour_itinerary_days WHERE experience_tour_id = ? ORDER BY day_number');
$itineraryDays->execute([$id]);
$itineraryDays = $itineraryDays->fetchAll();

$page_title = $tour['title'];
$page_eyebrow = 'Experiential · ' . $tour['type_name'];
$active_nav = 'experience-tours';
$page_action_html = '<a class="btn btn--ghost" href="' . h(url('/admin/experience-tours/form.php?id=' . $id)) . '">Edit basics</a>';

require __DIR__ . '/../includes/header.php';
$returnUrl = $_SERVER['REQUEST_URI'];
?>

<?php if (count($selectedIds) < 1): ?>
  <div class="flash flash--error">This tour needs at least 1 destination before it can be published. Currently has 0.</div>
<?php endif; ?>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Destinations (<?= h($tour['type_name']) ?>)</div>
  </div>
  <div class="panel__body">
    <?php if (!$typeDestinations): ?>
      <p class="section-hint">No <?= h($tour['type_name']) ?> destinations exist yet. <a href="<?= h(url('/admin/experience-destinations/form.php')) ?>">Add one</a> first.</p>
    <?php else: ?>
      <form method="post" action="<?= h(url('/admin/experience-tours/destinations.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="tour_id" value="<?= $id ?>">
        <div class="checkbox-grid">
          <?php foreach ($typeDestinations as $destination): ?>
            <label class="checkbox-row">
              <input type="checkbox" name="destination_ids[]" value="<?= (int) $destination['id'] ?>" <?= in_array($destination['id'], $selectedIds, true) ? 'checked' : '' ?>>
              <?= h($destination['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn--primary btn--sm">Save destinations</button>
        </div>
        <p class="section-hint" style="margin-top:10px;">Selecting a new destination automatically pulls its activities in below &mdash; edit or remove them freely afterward.</p>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Itinerary builder</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/experience-tours/itinerary/form.php?tour_id=' . $id)) ?>">Add day</a>
  </div>
  <div class="panel__body">
    <?php if (!$itineraryDays): ?>
      <div class="empty-state">
        <div class="empty-state__title">No itinerary days added yet</div>
        <div class="empty-state__body">Entirely optional &mdash; add day-by-day details only if this tour needs them alongside or instead of the activities list below.</div>
      </div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($itineraryDays as $day): ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title">Day <?= (int) $day['day_number'] ?> &middot; <?= h($day['title']) ?></div>
              <?php if ($day['description']): ?><div class="sub-row__meta"><?= h(mb_strimwidth($day['description'], 0, 140, '…')) ?></div><?php endif; ?>
            </div>
            <div class="table__actions">
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-tours/itinerary/form.php?tour_id=' . $id . '&id=' . $day['id'])) ?>">Edit</a>
              <form method="post" action="<?= h(url('/admin/experience-tours/itinerary/delete.php')) ?>" onsubmit="return confirm('Remove this itinerary day?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $day['id'] ?>">
                <input type="hidden" name="tour_id" value="<?= $id ?>">
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
    <div class="panel__title">Activities in this tour</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/experience-tours/activities/form.php?tour_id=' . $id)) ?>">Add activity</a>
  </div>
  <div class="panel__body">
    <?php if (!$activities): ?>
      <div class="empty-state">
        <div class="empty-state__title">No activities yet</div>
        <div class="empty-state__body">Select destinations above to pull their activities in, or add one manually.</div>
      </div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($activities as $activity): $g = media_count('experience_tour_activity', $activity['id']); ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title"><?= h($activity['title']) ?></div>
              <div class="sub-row__meta"><?= h(mb_strimwidth($activity['description'] ?? '', 0, 140, '…')) ?></div>
              <div class="sub-row__meta"><?= $g ?> gallery image<?= $g === 1 ? '' : 's' ?></div>
            </div>
            <div class="table__actions">
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=experience_tour_activity&entity_id=' . $activity['id'] . '&title=' . urlencode($activity['title'] . ' gallery') . '&back=' . urlencode($returnUrl))) ?>">Gallery</a>
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-tours/activities/form.php?tour_id=' . $id . '&id=' . $activity['id'])) ?>">Edit</a>
              <form method="post" action="<?= h(url('/admin/experience-tours/activities/delete.php')) ?>" onsubmit="return confirm('Remove this activity?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $activity['id'] ?>">
                <input type="hidden" name="tour_id" value="<?= $id ?>">
                <button type="submit" class="btn btn--danger btn--sm">Remove</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel__header"><div class="panel__title">Galleries</div></div>
  <div class="panel__body">
    <div class="sub-list">
      <?php
      $galleries = ['experience_tour' => 'Main gallery', 'experience_tour_overview' => 'Full overview gallery', 'experience_tour_accommodation' => 'Accommodation gallery'];
      foreach ($galleries as $type => $label):
          $count = media_count($type, $id);
      ?>
        <div class="sub-row">
          <div class="sub-row__body">
            <div class="sub-row__title"><?= h($label) ?></div>
            <div class="sub-row__meta"><?= $count ?> image<?= $count === 1 ? '' : 's' ?></div>
          </div>
          <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=' . $type . '&entity_id=' . $id . '&title=' . urlencode($tour['title'] . ' · ' . $label) . '&back=' . urlencode($returnUrl))) ?>">Manage</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
