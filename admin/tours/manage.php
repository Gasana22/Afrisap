<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT t.*, c.name AS category_name, o.company_name AS operator_name
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    LEFT JOIN tour_operators o ON o.id = t.operator_id
    WHERE t.id = ?');
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) {
    flash_set('error', 'That tour no longer exists.');
    redirect('/admin/tours/index.php');
}

$allDestinations = db()->query('SELECT d.id, d.name, c.name AS country_name FROM destinations d JOIN countries c ON c.id = d.country_id ORDER BY c.name, d.name')->fetchAll();

$selectedStmt = db()->prepare('SELECT destination_id FROM tour_destinations WHERE tour_id = ?');
$selectedStmt->execute([$id]);
$selectedIds = array_column($selectedStmt->fetchAll(), 'destination_id');

$itineraryDays = db()->prepare('SELECT * FROM tour_itinerary_days WHERE tour_id = ? ORDER BY day_number');
$itineraryDays->execute([$id]);
$itineraryDays = $itineraryDays->fetchAll();

$activities = db()->prepare('SELECT * FROM tour_activities WHERE tour_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$faqs = db()->prepare('SELECT * FROM tour_faqs WHERE tour_id = ? ORDER BY sort_order, id');
$faqs->execute([$id]);
$faqs = $faqs->fetchAll();

$page_title = $tour['title'];
$page_eyebrow = 'Safari · ' . $tour['category_name'];
$active_nav = 'tours';
$page_action_html = '<a class="btn btn--ghost" href="' . h(url('/admin/tours/form.php?id=' . $id)) . '">Edit basics</a>';

require __DIR__ . '/../includes/header.php';

$returnUrl = $_SERVER['REQUEST_URI'];
?>

<?php if (count($selectedIds) < 2): ?>
  <div class="flash flash--error">This tour needs at least 2 destinations before it can be published. Currently has <?= count($selectedIds) ?>.</div>
<?php endif; ?>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Itinerary builder</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/tours/itinerary/form.php?tour_id=' . $id)) ?>">Add day</a>
  </div>
  <div class="panel__body">
    <?php if (!$itineraryDays): ?>
      <div class="empty-state">
        <div class="empty-state__title">No itinerary days added yet</div>
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
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/tours/itinerary/form.php?tour_id=' . $id . '&id=' . $day['id'])) ?>">Edit</a>
              <form method="post" action="<?= h(url('/admin/tours/itinerary/delete.php')) ?>" onsubmit="return confirm('Remove this itinerary day?');">
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
    <div class="panel__title">Destinations</div>
  </div>
  <div class="panel__body">
    <form method="post" action="<?= h(url('/admin/tours/destinations.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="tour_id" value="<?= $id ?>">
      <div class="checkbox-grid">
        <?php foreach ($allDestinations as $destination): ?>
          <label class="checkbox-row">
            <input type="checkbox" name="destination_ids[]" value="<?= (int) $destination['id'] ?>" <?= in_array($destination['id'], $selectedIds) ? 'checked' : '' ?>>
            <?= h($destination['name']) ?> <span class="table__meta">(<?= h($destination['country_name']) ?>)</span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--sm">Save destinations (pick at least 2)</button>
      </div>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Activities in this tour</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/tours/activities/form.php?tour_id=' . $id)) ?>">Add activity</a>
  </div>
  <div class="panel__body">
    <?php if (!$activities): ?>
      <div class="empty-state">
        <div class="empty-state__title">No activities added yet</div>
      </div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($activities as $activity): $g = media_count('tour_activity', $activity['id']); ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title"><?= h($activity['title']) ?></div>
              <div class="sub-row__meta"><?= h(mb_strimwidth($activity['description'] ?? '', 0, 120, '…')) ?></div>
              <div class="sub-row__meta"><?= $g ?> gallery image<?= $g === 1 ? '' : 's' ?></div>
            </div>
            <div class="table__actions">
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=tour_activity&entity_id=' . $activity['id'] . '&title=' . urlencode($activity['title'] . ' gallery') . '&back=' . urlencode($returnUrl))) ?>">Gallery</a>
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/tours/activities/form.php?tour_id=' . $id . '&id=' . $activity['id'])) ?>">Edit</a>
              <form method="post" action="<?= h(url('/admin/tours/activities/delete.php')) ?>" onsubmit="return confirm('Remove this activity?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $activity['id'] ?>">
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
    <div class="panel__title">Frequently asked questions</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/tours/faqs/form.php?tour_id=' . $id)) ?>">Add FAQ</a>
  </div>
  <div class="panel__body">
    <?php if (!$faqs): ?>
      <div class="empty-state">
        <div class="empty-state__title">No FAQs yet</div>
      </div>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($faqs as $faq): ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title"><?= h($faq['question']) ?></div>
              <div class="sub-row__meta"><?= h(mb_strimwidth($faq['answer'], 0, 140, '…')) ?></div>
            </div>
            <div class="table__actions">
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/tours/faqs/form.php?tour_id=' . $id . '&id=' . $faq['id'])) ?>">Edit</a>
              <form method="post" action="<?= h(url('/admin/tours/faqs/delete.php')) ?>" onsubmit="return confirm('Remove this FAQ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $faq['id'] ?>">
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
  <div class="panel__header"><div class="panel__title">Galleries</div></div>
  <div class="panel__body">
    <div class="sub-list">
      <?php
      $galleries = [
          'tour' => 'Main gallery',
          'tour_overview' => 'Full overview gallery',
          'tour_hotel' => 'Hotel gallery',
          'tour_vehicle' => 'Vehicle gallery',
          'tour_flight' => 'Flight gallery',
      ];
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
