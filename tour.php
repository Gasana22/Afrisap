<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT t.*, c.name AS category_name, o.company_name AS operator_name, o.logo_path AS operator_logo, o.phone AS operator_phone, o.email AS operator_email
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    LEFT JOIN tour_operators o ON o.id = t.operator_id
    WHERE t.id = ? AND t.status = 'published'");
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) {
    http_response_code(404);
    $page_title = 'Tour not found — Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That tour isn\'t available. <a href="' . h(url('/tours.php')) . '">Browse all tours</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$destinations = db()->prepare('SELECT d.id, d.name, c.name AS country_name FROM tour_destinations td JOIN destinations d ON d.id = td.destination_id JOIN countries c ON c.id = d.country_id WHERE td.tour_id = ? ORDER BY d.name');
$destinations->execute([$id]);
$destinations = $destinations->fetchAll();

$activities = db()->prepare('SELECT * FROM tour_activities WHERE tour_id = ? ORDER BY sort_order, id');
$activities->execute([$id]);
$activities = $activities->fetchAll();

$faqs = db()->prepare('SELECT * FROM tour_faqs WHERE tour_id = ? ORDER BY sort_order, id');
$faqs->execute([$id]);
$faqs = $faqs->fetchAll();

$mainGallery = get_media('tour', $id);
$overviewGallery = get_media('tour_overview', $id);
$hotelGallery = get_media('tour_hotel', $id);
$vehicleGallery = get_media('tour_vehicle', $id);
$flightGallery = get_media('tour_flight', $id);

$bookingSent = isset($_GET['sent']) && $_GET['sent'] === '1';
$netPrice = (float) $tour['price'] * (1 - (float) $tour['discount_percent'] / 100);

$page_title = $tour['title'] . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow"><?= h($tour['category_name']) ?> &middot; <?= h($tour['budget_type']) ?></p>
    <h1 class="page-header__title"><?= h($tour['title']) ?></h1>
    <?php if ($destinations): ?>
      <p class="page-header__lead"><?php foreach ($destinations as $i => $d): ?><?= $i ? ' &middot; ' : '' ?><a href="<?= h(url('/destination.php?id=' . $d['id'])) ?>" style="color:inherit;text-decoration:underline;"><?= h($d['name']) ?></a><?php endforeach; ?></p>
    <?php endif; ?>
  </div>
</header>

<?php if ($mainGallery): ?>
<div class="wrap" style="margin-top:-1px;">
  <div class="gallery-strip gallery-strip--hero">
    <?php foreach ($mainGallery as $img): ?>
      <img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy">
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <div class="detail-grid">
      <div class="detail-main">
        <?php if ($tour['short_overview']): ?><p class="detail-lead"><?= nl2br(h($tour['short_overview'])) ?></p><?php endif; ?>

        <?php if ($tour['full_overview']): ?>
          <h2 class="detail-heading">Full overview</h2>
          <p class="detail-text"><?= nl2br(h($tour['full_overview'])) ?></p>
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
              $activityGallery = get_media('tour_activity', $activity['id']);
            ?>
              <div class="activity-item">
                <h3 class="activity-item__title"><?= h($activity['title']) ?></h3>
                <?php if ($activity['description']): ?><p class="activity-item__body"><?= nl2br(h($activity['description'])) ?></p><?php endif; ?>
                <?php if ($activityGallery): ?>
                  <div class="gallery-strip"><?php foreach ($activityGallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($tour['hotel_info']): ?>
          <h2 class="detail-heading">Hotel</h2>
          <p class="detail-text"><?= nl2br(h($tour['hotel_info'])) ?></p>
          <?php if ($hotelGallery): ?><div class="gallery-strip"><?php foreach ($hotelGallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div><?php endif; ?>
        <?php endif; ?>

        <?php if ($tour['vehicle_info']): ?>
          <h2 class="detail-heading">Vehicle</h2>
          <p class="detail-text"><?= nl2br(h($tour['vehicle_info'])) ?></p>
          <?php if ($vehicleGallery): ?><div class="gallery-strip"><?php foreach ($vehicleGallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div><?php endif; ?>
        <?php endif; ?>

        <?php if ($tour['flight_info']): ?>
          <h2 class="detail-heading">Flights</h2>
          <p class="detail-text"><?= nl2br(h($tour['flight_info'])) ?></p>
          <?php if ($flightGallery): ?><div class="gallery-strip"><?php foreach ($flightGallery as $img): ?><img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy"><?php endforeach; ?></div><?php endif; ?>
        <?php endif; ?>

        <?php if ($tour['includes'] || $tour['excludes']): ?>
          <h2 class="detail-heading">Includes &amp; excludes</h2>
          <div class="includes-grid">
            <?php if ($tour['includes']): ?><div><p class="includes-grid__label">Includes</p><p class="detail-text"><?= nl2br(h($tour['includes'])) ?></p></div><?php endif; ?>
            <?php if ($tour['excludes']): ?><div><p class="includes-grid__label includes-grid__label--excl">Excludes</p><p class="detail-text"><?= nl2br(h($tour['excludes'])) ?></p></div><?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($faqs): ?>
          <h2 class="detail-heading">Frequently asked questions</h2>
          <div class="faq-list">
            <?php foreach ($faqs as $faq): ?>
              <details class="faq-item">
                <summary><?= h($faq['question']) ?></summary>
                <p><?= nl2br(h($faq['answer'])) ?></p>
              </details>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <aside class="detail-side">
        <div class="side-card side-card--price">
          <p class="side-card__permit-no">ITINERARY NO. SS&ndash;<?= str_pad((string) $tour['id'], 4, '0', STR_PAD_LEFT) ?></p>
          <div class="side-card__price">
            $<?= number_format($netPrice, 0) ?>
            <?php if ((float) $tour['discount_percent'] > 0): ?>
              <span class="side-card__was">$<?= number_format((float) $tour['price'], 0) ?></span>
            <?php endif; ?>
            <span class="side-card__unit">/ person</span>
          </div>
          <ul class="side-card__facts">
            <li><span>Duration</span><span><?= (int) $tour['days'] ?> days</span></li>
            <?php if ($tour['scheduled_date']): ?>
              <li><span>Departs</span><span><?= h(formatDate($tour['scheduled_date'], 'M j, Y')) ?></span></li>
            <?php endif; ?>
            <li><span>Group size</span><span><?= (int) $tour['min_pax'] ?>–<?= (int) $tour['max_pax'] ?> people</span></li>
            <li><span>Budget tier</span><span><?= h($tour['budget_type']) ?></span></li>
          </ul>
          <a href="#enquire" class="btn btn--primary" style="width:100%;justify-content:center;">Enquire About This Tour</a>
        </div>

        <?php if ($tour['operator_name']): ?>
          <div class="side-card">
            <p class="side-card__title">Tour operator</p>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
              <?php if ($tour['operator_logo']): ?><img src="<?= h(url('/' . $tour['operator_logo'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;"><?php endif; ?>
              <strong><?= h($tour['operator_name']) ?></strong>
            </div>
            <?php if ($tour['operator_phone']): ?><p class="side-card__body"><?= h($tour['operator_phone']) ?></p><?php endif; ?>
            <?php if ($tour['operator_email']): ?><p class="side-card__body"><?= h($tour['operator_email']) ?></p><?php endif; ?>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<section class="section section--savanna" id="enquire">
  <div class="wrap" style="max-width:640px;">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Enquire</p>
      <h2 class="section__title">Ask about this tour</h2>
      <p class="section__lead">Send your details and <?= h($tour['operator_name'] ?: 'our team') ?> will get back to you to confirm dates and availability.</p>
    </div>

    <?php if ($bookingSent): ?>
      <div class="flash flash--success">Thanks — your enquiry has been sent. We'll be in touch shortly.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/book.php')) ?>">
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
