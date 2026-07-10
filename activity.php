<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT a.*, o.company_name AS operator_name, o.phone AS operator_phone, o.email AS operator_email, d.id AS destination_id, d.name AS destination_name
    FROM activities a
    LEFT JOIN tour_operators o ON o.id = a.operator_id
    LEFT JOIN destinations d ON d.id = a.destination_id
    WHERE a.id = ?');
$stmt->execute([$id]);
$activity = $stmt->fetch();

if (!$activity) {
    http_response_code(404);
    $page_title = 'Activity not found — Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That activity isn\'t available. <a href="' . h(url('/tours.php')) . '">Browse tours</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$gallery = get_media('activity', $id);

$tours = db()->prepare("SELECT DISTINCT t.id, t.title, t.budget_type, t.price, t.days, c.name AS category_name
    FROM tour_activities ta
    JOIN tours t ON t.id = ta.tour_id
    JOIN tour_categories c ON c.id = t.category_id
    WHERE ta.activity_id = ? AND t.status = 'published'
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

$page_title = $activity['name'] . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Activity</p>
    <h1 class="page-header__title"><?= h($activity['name']) ?></h1>
    <?php if ($activity['duration_hours'] !== null): ?><p class="page-header__lead"><?= h((string) $activity['duration_hours']) ?> hours</p><?php endif; ?>
  </div>
</header>

<?php if ($gallery): ?>
<div class="wrap" style="margin-top:-1px;">
  <div class="gallery-strip gallery-strip--hero">
    <?php foreach ($gallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <div class="detail-grid">
      <div class="detail-main">
        <?php if ($activity['short_description']): ?><p class="detail-lead"><?= nl2br(h($activity['short_description'])) ?></p><?php endif; ?>
        <?php if ($activity['full_description']): ?>
          <h2 class="detail-heading">About this activity</h2>
          <p class="detail-text"><?= nl2br(h($activity['full_description'])) ?></p>
        <?php endif; ?>
      </div>
      <aside class="detail-side">
        <?php if ($activity['operator_name'] || $activity['phone'] || $activity['email'] || $activity['destination_name']): ?>
          <div class="side-card">
            <h3 class="side-card__title">Details</h3>
            <?php if ($activity['operator_name']): ?><p class="side-card__body"><strong><?= h($activity['operator_name']) ?></strong></p><?php endif; ?>
            <?php if ($activity['phone']): ?><p class="side-card__body"><?= h($activity['phone']) ?></p><?php endif; ?>
            <?php if ($activity['email']): ?><p class="side-card__body"><?= h($activity['email']) ?></p><?php endif; ?>
            <?php if ($activity['destination_name']): ?>
              <a href="<?= h(url('/destination.php?id=' . $activity['destination_id'])) ?>" class="side-card__link"><?= h($activity['destination_name']) ?></a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<?php if ($tours): ?>
<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Book It</p>
      <h2 class="section__title">Itineraries featuring <?= h($activity['name']) ?></h2>
    </div>
    <div class="card-grid">
      <?php foreach ($tours as $tour): ?>
        <a href="<?= h(url('/tour.php?id=' . $tour['id'])) ?>" class="tour-card">
          <div class="tour-card__media"><span class="tour-card__badge"><?= h($tour['budget_type']) ?></span></div>
          <div class="tour-card__body">
            <p class="tour-card__meta"><?= h($tour['category_name']) ?> &middot; <?= (int) $tour['days'] ?> days</p>
            <h3 class="tour-card__title"><?= h($tour['title']) ?></h3>
            <div class="tour-card__footer">
              <div class="tour-card__price">$<?= number_format((float) $tour['price'], 0) ?></div>
              <span class="btn btn--dark" style="padding:8px 14px;font-size:13px;">View</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($destinationPages): ?>
<section class="section">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Where to Find It</p>
      <h2 class="section__title">Destinations offering <?= h($activity['name']) ?></h2>
    </div>
    <div class="tile-rail">
      <?php foreach ($destinationPages as $row): ?>
        <a href="<?= h(url('/destination.php?id=' . $row['destination_id'])) ?>" class="tile">
          <h3 class="tile__title"><?= h($row['destination_name']) ?></h3>
          <p class="tile__meta">Listed as "<?= h($row['title']) ?>"</p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$tours && !$destinationPages): ?>
<section class="section">
  <div class="wrap"><p class="empty-note">This activity isn't featured on any tour or destination page yet.</p></div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
