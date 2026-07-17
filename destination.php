<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT d.*, c.name AS country_name FROM destinations d JOIN countries c ON c.id = d.country_id WHERE d.id = ?');
$stmt->execute([$id]);
$destination = $stmt->fetch();

if (!$destination) {
    http_response_code(404);
    $page_title = 'Destination not found — Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That destination isn\'t available. <a href="' . h(url('/tours.php')) . '">Browse tours</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$animals = db()->prepare('SELECT name FROM destination_animals WHERE destination_id = ? ORDER BY name');
$animals->execute([$id]);
$animals = $animals->fetchAll();

$birds = db()->prepare('SELECT name FROM destination_birds WHERE destination_id = ? ORDER BY name');
$birds->execute([$id]);
$birds = $birds->fetchAll();

$activities = db()->prepare('SELECT * FROM destination_activities WHERE destination_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$gallery = get_media('destination', $id);

$relatedTours = db()->prepare("SELECT t.id, t.title, t.budget_type, t.price, t.discount_percent, t.days, c.name AS category_name
    FROM tours t
    JOIN tour_destinations td ON td.tour_id = t.id
    JOIN tour_categories c ON c.id = t.category_id
    WHERE td.destination_id = ? AND t.status = 'published'
    ORDER BY t.created_at DESC");
$relatedTours->execute([$id]);
$relatedTours = $relatedTours->fetchAll();

$page_title = $destination['name'] . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Destination &middot; <?= h($destination['country_name']) ?></p>
    <h1 class="page-header__title"><?= h($destination['name']) ?></h1>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <div class="detail-grid">
      <div class="detail-main">
        <?php if ($destination['overview'] || $gallery): ?>
          <h2 class="detail-heading">Overview</h2>
          <?php if ($destination['overview']): ?><p class="detail-text"><?= nl2br(h($destination['overview'])) ?></p><?php endif; ?>
          <?php if ($gallery): ?>
            <div class="gallery-strip"><?php foreach ($gallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($activities): ?>
          <h2 class="detail-heading">Popular activities</h2>
          <div class="activity-list">
            <?php foreach ($activities as $activity):
              $activityGallery = get_media('destination_activity', $activity['id']);
            ?>
              <div class="activity-item">
                <div class="activity-item__icon"><?= render_nav_glyph(nav_icon_for($activity['title'])) ?></div>
                <div class="activity-item__content">
                  <h3 class="activity-item__title"><?= h($activity['title']) ?></h3>
                  <?php if ($activity['description']): ?><p class="activity-item__body"><?= nl2br(h($activity['description'])) ?></p><?php endif; ?>
                  <?php if ($activityGallery): ?>
                    <div class="gallery-strip"><?php foreach ($activityGallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($destination['why_consider']): ?>
          <h2 class="detail-heading">Why consider <?= h($destination['name']) ?></h2>
          <p class="detail-text"><?= nl2br(h($destination['why_consider'])) ?></p>
        <?php endif; ?>

        <?php if ($destination['additional_info']): ?>
          <h2 class="detail-heading">Additional information</h2>
          <p class="detail-text"><?= nl2br(h($destination['additional_info'])) ?></p>
        <?php endif; ?>
      </div>

      <aside class="detail-side">
        <?php if ($animals): ?>
          <div class="side-card">
            <p class="side-card__title"><?= render_nav_glyph('paw') ?>Animals found here</p>
            <div class="tag-list">
              <?php foreach ($animals as $a): ?><span class="tag-static"><?= h($a['name']) ?></span><?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($birds): ?>
          <div class="side-card">
            <p class="side-card__title"><?= render_nav_glyph('bird') ?>Birds found here</p>
            <div class="tag-list">
              <?php foreach ($birds as $b): ?><span class="tag-static"><?= h($b['name']) ?></span><?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Book a Tour Here</p>
      <h2 class="section__title">Itineraries visiting <?= h($destination['name']) ?></h2>
    </div>
    <?php if (!$relatedTours): ?>
      <p class="empty-note">No published tours include this destination yet. <a href="<?= h(url('/quote.php?type=safari')) ?>">Ask us for a custom itinerary</a>.</p>
    <?php else: ?>
      <div class="card-grid">
        <?php foreach ($relatedTours as $tour): ?>
          <a href="<?= h(url('/tour.php?id=' . $tour['id'])) ?>" class="tour-card">
            <div class="tour-card__media">
              <?php if ($cover = get_cover_image('tour', $tour['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?>
              <span class="tour-card__badge"><?= h($tour['budget_type']) ?></span>
            </div>
            <div class="tour-card__body">
              <p class="tour-card__meta"><?= h($tour['category_name']) ?> &middot; <?= (int) $tour['days'] ?> days</p>
              <h3 class="tour-card__title"><?= h($tour['title']) ?></h3>
              <div class="tour-card__footer">
                <div class="tour-card__price"><span class="tour-card__price-label">Starting from</span>$<?= number_format((float) $tour['price'], 0) ?><span class="tour-card__price-unit">/ person</span></div>
                <span class="btn btn--dark" style="padding:8px 14px;font-size:13px;">View Details</span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
