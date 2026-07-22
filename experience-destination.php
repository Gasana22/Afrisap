<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT ed.*, et.name AS type_name, et.slug AS type_slug FROM experience_destinations ed JOIN experience_types et ON et.id = ed.experience_type_id WHERE ed.id = ?');
$stmt->execute([$id]);
$destination = $stmt->fetch();

if (!$destination) {
    http_response_code(404);
    $page_title = 'Destination not found - Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That destination isn\'t available. <a href="' . h(url('/experiences.php')) . '">Browse experiences</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$activities = db()->prepare('SELECT * FROM experience_destination_activities WHERE experience_destination_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$gallery = get_media('experience_destination', $id);

$relatedTours = db()->prepare("SELECT et.id, et.title, et.price, et.discount_percent, et.days
    FROM experience_tours et
    JOIN experience_tour_destinations etd ON etd.experience_tour_id = et.id
    WHERE etd.experience_destination_id = ? AND et.status = 'published'
    ORDER BY et.created_at DESC");
$relatedTours->execute([$id]);
$relatedTours = $relatedTours->fetchAll();

$page_title = $destination['name'] . ' - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow"><?= h($destination['type_name']) ?></p>
    <h1 class="page-header__title"><?= h($destination['name']) ?></h1>
    <?php if ($destination['location']): ?><p class="page-header__lead"><?= h($destination['location']) ?></p><?php endif; ?>
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
        <?php if ($destination['short_overview']): ?>
          <p class="detail-lead" style="max-width:760px;"><?= nl2br(h($destination['short_overview'])) ?></p>
        <?php endif; ?>

        <?php if ($videoEmbedUrl = youtube_embed_url($destination['video_url'])): ?>
          <div class="video-embed"><iframe src="<?= h($videoEmbedUrl) ?>" title="<?= h($destination['name']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
        <?php endif; ?>

        <?php if ($activities): ?>
          <h2 class="detail-heading">Activities &amp; cultural uniqueness</h2>
          <div class="activity-list">
            <?php foreach ($activities as $activity):
              $activityGallery = get_media('experience_destination_activity', $activity['id']);
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
      </div>

      <?php $pricedActivities = array_filter($activities, static fn ($a) => $a['amount'] !== null); ?>
      <?php if ($pricedActivities): ?>
        <aside class="detail-side">
          <div class="side-card">
            <p class="side-card__title"><?= render_nav_glyph('tag') ?>Activity pricing</p>
            <div class="list-rows">
              <?php foreach ($pricedActivities as $activity): ?>
                <div class="list-row">
                  <div class="list-row__title"><?= h($activity['title']) ?></div>
                  <div class="list-row__meta">$<?= number_format((float) $activity['amount'], 0) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </aside>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Book This Experience</p>
      <h2 class="section__title">Tours featuring <?= h($destination['name']) ?></h2>
    </div>
    <?php if (!$relatedTours): ?>
      <p class="empty-note">No published tours feature this destination yet. <a href="<?= h(url('/quote.php?type=experiential')) ?>">Ask us for a custom itinerary</a>.</p>
    <?php else: ?>
      <div class="card-grid">
        <?php foreach ($relatedTours as $tour): ?>
          <a href="<?= h(url('/experience-tour.php?id=' . $tour['id'])) ?>" class="tour-card">
            <div class="tour-card__media">
              <?php if ($cover = get_cover_image('experience_tour', $tour['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?>
              <span class="tour-card__badge"><?= h($destination['type_name']) ?></span>
            </div>
            <div class="tour-card__body">
              <p class="tour-card__meta"><?= (int) $tour['days'] ?> days</p>
              <h3 class="tour-card__title"><?= h($tour['title']) ?></h3>
              <div class="tour-card__footer">
                <div class="tour-card__price"><span class="tour-card__price-label">Starting from</span>$<?= number_format((float) $tour['price'] * (1 - (float) $tour['discount_percent'] / 100), 0) ?><span class="tour-card__price-unit">/ person</span></div>
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
