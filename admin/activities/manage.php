<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT a.*, o.company_name AS operator_name, d.name AS destination_name
    FROM activities a
    LEFT JOIN tour_operators o ON o.id = a.operator_id
    LEFT JOIN destinations d ON d.id = a.destination_id
    WHERE a.id = ?');
$stmt->execute([$id]);
$activity = $stmt->fetch();

if (!$activity) {
    flash_set('error', 'That activity no longer exists.');
    redirect('/admin/activities/index.php');
}

$tours = db()->prepare("SELECT DISTINCT t.id, t.title, t.status
    FROM tour_activities ta
    JOIN tours t ON t.id = ta.tour_id
    WHERE ta.activity_id = ?
    ORDER BY t.title");
$tours->execute([$id]);
$tours = $tours->fetchAll();

$destinationPages = db()->prepare("SELECT da.title, d.id AS destination_id, d.name AS destination_name
    FROM destination_activities da
    JOIN destinations d ON d.id = da.destination_id
    WHERE da.activity_id = ?
    ORDER BY d.name");
$destinationPages->execute([$id]);
$destinationPages = $destinationPages->fetchAll();

$galleryCount = media_count('activity', $id);

$page_title = $activity['name'];
$page_eyebrow = 'Activities';
$active_nav = 'activities';
$page_action_html = '<a class="btn btn--ghost" href="' . h(url('/admin/activities/form.php?id=' . $id)) . '">Edit details</a>';

require __DIR__ . '/../includes/header.php';
$returnUrl = $_SERVER['REQUEST_URI'];
?>

<div class="panel">
  <div class="panel__body">
    <p style="margin:0 0 8px; opacity:0.75; font-size:13.5px;"><?= nl2br(h($activity['short_description'] ?: 'No description yet.')) ?></p>
    <p class="table__meta" style="margin:0;">
      <?= $activity['duration_hours'] !== null ? h((string) $activity['duration_hours']) . ' hrs · ' : '' ?>
      <?= h($activity['operator_name'] ?? 'No company linked') ?>
      <?= $activity['destination_name'] ? ' · ' . h($activity['destination_name']) : '' ?>
    </p>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Gallery</div>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=activity&entity_id=' . $id . '&title=' . urlencode($activity['name'] . ' gallery') . '&back=' . urlencode($returnUrl))) ?>">Manage gallery (<?= $galleryCount ?>)</a>
  </div>
</div>

<div class="panel">
  <div class="panel__header"><div class="panel__title">Itineraries featuring this activity</div></div>
  <div class="panel__body">
    <?php if (!$tours): ?>
      <p class="section-hint" style="margin:0;">Not used in any tour yet.</p>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($tours as $tour): ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title"><?= h($tour['title']) ?></div>
              <div class="sub-row__meta"><?= h(ucfirst($tour['status'])) ?></div>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/tours/manage.php?id=' . $tour['id'])) ?>">Open tour</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel__header"><div class="panel__title">Destination pages featuring this activity</div></div>
  <div class="panel__body">
    <?php if (!$destinationPages): ?>
      <p class="section-hint" style="margin:0;">Not featured as a popular activity on any destination yet.</p>
    <?php else: ?>
      <div class="sub-list">
        <?php foreach ($destinationPages as $row): ?>
          <div class="sub-row">
            <div class="sub-row__body">
              <div class="sub-row__title"><?= h($row['destination_name']) ?></div>
              <div class="sub-row__meta">Listed as "<?= h($row['title']) ?>"</div>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/destinations/manage.php?id=' . $row['destination_id'])) ?>">Open destination</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
