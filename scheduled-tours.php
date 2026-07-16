<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$tours = db()->query("SELECT t.id, t.title, t.budget_type, t.price, t.discount_percent, t.days, t.scheduled_date, t.short_overview, c.name AS category_name
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    WHERE t.status = 'published' AND t.scheduled_date IS NOT NULL AND t.scheduled_date >= CURDATE()
    ORDER BY t.scheduled_date ASC")->fetchAll();

$page_title = 'Scheduled Tours — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Specialised Tours</p>
    <h1 class="page-header__title">Scheduled Tours</h1>
    <p class="page-header__lead">Regular tours that run on a fixed date, so you can join a departure that's already planned instead of building your own itinerary from scratch.</p>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <?php if (!$tours): ?>
      <p class="empty-note">No departures are on the calendar yet. <a href="<?= h(url('/contact.php')) ?>">Ask us</a> about upcoming dates, or <a href="<?= h(url('/create-your-own-tour.php')) ?>">build your own itinerary</a> instead.</p>
    <?php else: ?>
      <div class="card-grid">
        <?php foreach ($tours as $tour): ?>
          <a href="<?= h(url('/tour.php?id=' . $tour['id'])) ?>" class="tour-card">
            <div class="tour-card__media">
              <?php if ($cover = get_cover_image('tour', $tour['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?>
              <span class="tour-card__badge"><?= h(formatDate($tour['scheduled_date'], 'M j, Y')) ?></span>
            </div>
            <div class="tour-card__body">
              <p class="tour-card__meta"><?= h($tour['category_name']) ?> &middot; <?= (int) $tour['days'] ?> days</p>
              <h3 class="tour-card__title"><?= h($tour['title']) ?></h3>
              <p class="tour-card__overview"><?= h(mb_strimwidth((string) $tour['short_overview'], 0, 110, '…')) ?></p>
              <div class="tour-card__footer">
                <div class="tour-card__price">
                  <span class="tour-card__price-label">Starting from</span>$<?= number_format((float) $tour['price'], 0) ?><span class="tour-card__price-unit">/ person</span>
                  <?php if ((float) $tour['discount_percent'] > 0): ?><small><?= (float) $tour['discount_percent'] ?>% off</small><?php endif; ?>
                </div>
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
