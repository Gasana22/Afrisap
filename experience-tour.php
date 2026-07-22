<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT et.*, ety.name AS type_name, sp.company_name AS provider_name
    FROM experience_tours et
    JOIN experience_types ety ON ety.id = et.experience_type_id
    LEFT JOIN service_providers sp ON sp.id = et.provider_id
    WHERE et.id = ? AND et.status = 'published'");
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) {
    http_response_code(404);
    $page_title = 'Tour not found - Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That tour isn\'t available. <a href="' . h(url('/experiences.php')) . '">Browse experiences</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$destinations = db()->prepare('SELECT ed.id, ed.name, ed.location FROM experience_tour_destinations etd JOIN experience_destinations ed ON ed.id = etd.experience_destination_id WHERE etd.experience_tour_id = ? ORDER BY ed.name');
$destinations->execute([$id]);
$destinations = $destinations->fetchAll();

$activities = db()->prepare('SELECT * FROM experience_tour_activities WHERE experience_tour_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$mainGallery = get_media('experience_tour', $id);
$overviewGallery = get_media('experience_tour_overview', $id);

$bookingSent = isset($_GET['sent']) && $_GET['sent'] === '1';
$netPrice = (float) $tour['price'] * (1 - (float) $tour['discount_percent'] / 100);

$page_title = $tour['title'] . ' - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow"><?= h($tour['type_name']) ?></p>
    <h1 class="page-header__title"><?= h($tour['title']) ?></h1>
    <?php if ($destinations): ?>
      <p class="page-header__lead"><?php foreach ($destinations as $i => $d): ?><?= $i ? ' &middot; ' : '' ?><a href="<?= h(url('/experience-destination.php?id=' . $d['id'])) ?>" style="color:inherit;text-decoration:underline;"><?= h($d['name']) ?></a><?php endforeach; ?></p>
    <?php endif; ?>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <div class="detail-grid">
      <div class="detail-main">
        <?php if ($tour['short_overview']): ?><p class="detail-lead"><?= nl2br(h($tour['short_overview'])) ?></p><?php endif; ?>

        <?php if ($videoEmbedUrl = youtube_embed_url($tour['video_url'])): ?>
          <div class="video-embed"><iframe src="<?= h($videoEmbedUrl) ?>" title="<?= h($tour['title']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
        <?php endif; ?>

        <?php if ($tour['full_overview'] || $mainGallery || $overviewGallery): ?>
          <h2 class="detail-heading">Full overview</h2>
          <?php if ($tour['full_overview']): ?><p class="detail-text"><?= nl2br(h($tour['full_overview'])) ?></p><?php endif; ?>
          <?php if ($mainGallery): ?>
            <div class="gallery-strip"><?php foreach ($mainGallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div>
          <?php endif; ?>
          <?php if ($overviewGallery): ?>
            <div class="gallery-strip"><?php foreach ($overviewGallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($tour['top_highlights']): ?>
          <h2 class="detail-heading">Top highlights</h2>
          <p class="detail-text"><?= nl2br(h($tour['top_highlights'])) ?></p>
        <?php endif; ?>

        <?php if ($activities): ?>
          <h2 class="detail-heading">Activities on this tour</h2>
          <div class="activity-list">
            <?php foreach ($activities as $activity):
              $activityGallery = get_media('experience_tour_activity', $activity['id']);
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

      <aside class="detail-side">
        <div class="side-card side-card--price">
          <p class="side-card__permit-no">ITINERARY NO. SX&ndash;<?= str_pad((string) $tour['id'], 4, '0', STR_PAD_LEFT) ?></p>
          <div class="side-card__price">
            <span class="side-card__price-label">Starting from</span>
            $<?= number_format($netPrice, 0) ?>
            <?php if ((float) $tour['discount_percent'] > 0): ?><span class="side-card__was">$<?= number_format((float) $tour['price'], 0) ?></span><?php endif; ?>
            <span class="side-card__unit">/ person</span>
          </div>
          <ul class="side-card__facts">
            <li><span>Tour name</span><span><?= h($tour['title']) ?></span></li>
            <?php
              $experienceLocations = array_unique(array_filter(array_column($destinations, 'location')));
            ?>
            <?php if ($experienceLocations): ?>
              <li><span>Location</span><span><?= h(implode(', ', $experienceLocations)) ?></span></li>
            <?php endif; ?>
            <?php if ($tour['provider_name']): ?>
              <li><span>Service provider in charge</span><span><?= h($tour['provider_name']) ?></span></li>
            <?php endif; ?>
            <li><span>Tour type</span><span><?= h($tour['type_name']) ?></span></li>
            <li><span>Duration</span><span><?= (int) $tour['days'] ?> days</span></li>
            <li><span>Group size</span><span><?= (int) $tour['min_pax'] ?>–<?= (int) $tour['max_pax'] ?> people</span></li>
          </ul>
          <a href="#enquire" class="btn btn--primary btn--pill" style="width:100%;justify-content:center;">Enquire About This Tour <span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
        </div>
      </aside>
    </div>
  </div>
</section>

<section class="section section--savanna" id="enquire">
  <div class="wrap" style="max-width:640px;">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Enquire</p>
      <h2 class="section__title">Ask about this tour</h2>
    </div>

    <?php if ($bookingSent): ?>
      <div class="flash flash--success">Thanks — your enquiry has been sent. We'll be in touch shortly.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/book-experience.php')) ?>">
        <input type="hidden" name="tour_id" value="<?= (int) $tour['id'] ?>">
        <?= csrf_field() ?>
        <div class="site-form__row">
          <label for="customer_name">Full name</label>
          <input type="text" id="customer_name" name="customer_name" required>
        </div>
        <div class="site-form__row">
          <label for="customer_email">Email</label>
          <input type="email" id="customer_email" name="customer_email" required>
        </div>
        <div class="site-form__row">
          <label for="customer_phone">Phone</label>
          <input type="text" id="customer_phone" name="customer_phone">
        </div>
        <div class="site-form__row">
          <label for="travel_date">Preferred travel date</label>
          <input type="date" id="travel_date" name="travel_date">
        </div>
        <div class="site-form__row">
          <label for="num_people">Number of people</label>
          <input type="number" id="num_people" name="num_people" min="1" value="<?= (int) $tour['min_pax'] ?>">
        </div>
        <div class="site-form__row">
          <label for="message">Message</label>
          <textarea id="message" name="message" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn--primary">Send Enquiry</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
