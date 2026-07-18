<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT o.*, c.name AS country_name
    FROM tour_operators o
    LEFT JOIN countries c ON c.id = o.country_id
    WHERE o.id = ?');
$stmt->execute([$id]);
$operator = $stmt->fetch();

if (!$operator) {
    http_response_code(404);
    $page_title = 'Operator not found - Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That operator isn\'t available. <a href="' . h(url('/operators.php')) . '">Browse all tour operators</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$tours = db()->prepare("SELECT t.id, t.title, t.budget_type, t.price, t.discount_percent, t.days, t.short_overview, c.name AS category_name
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    WHERE t.operator_id = ? AND t.status = 'published'
    ORDER BY t.created_at DESC");
$tours->execute([$id]);
$tours = $tours->fetchAll();

$page_title = $operator['company_name'] . ' - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Tour Operator</p>
    <h1 class="page-header__title"><?= h($operator['company_name']) ?></h1>
    <?php if ($operator['tour_type']): ?><p class="page-header__lead"><?= h($operator['tour_type']) ?></p><?php endif; ?>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <div class="detail-grid">
      <div>
        <?php if ($operator['profile_image_path']): ?>
          <img src="<?= h(url('/' . $operator['profile_image_path'])) ?>" alt="" style="width:100%;border-radius:var(--radius);margin-bottom:28px;">
        <?php endif; ?>

        <?php if ($operator['overview']): ?>
          <h2 style="font-family:var(--font-display);font-size:20px;margin:0 0 10px;">Overview</h2>
          <p style="white-space:pre-line;opacity:0.85;margin:0 0 28px;"><?= h($operator['overview']) ?></p>
        <?php endif; ?>

        <?php if ($tours): ?>
          <h2 style="font-family:var(--font-display);font-size:20px;margin:0 0 16px;">Tours by <?= h($operator['company_name']) ?></h2>
          <div class="card-grid">
            <?php foreach ($tours as $tour): ?>
              <a href="<?= h(url('/tour.php?id=' . $tour['id'])) ?>" class="tour-card">
                <div class="tour-card__media">
                  <?php if ($cover = get_cover_image('tour', $tour['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?>
                  <span class="tour-card__badge"><?= h($tour['budget_type']) ?></span>
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
        <?php else: ?>
          <p class="empty-note">No published tours are linked to this operator yet.</p>
        <?php endif; ?>
      </div>

      <aside class="detail-side">
        <div class="side-card">
          <p class="side-card__title"><?= render_nav_glyph('briefcase') ?>Company details</p>
          <?php if ($operator['logo_path']): ?><img src="<?= h(url('/' . $operator['logo_path'])) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;margin-bottom:10px;"><?php endif; ?>
          <?php if ($operator['office_location']): ?><p class="side-card__body"><strong>Office:</strong> <?= h($operator['office_location']) ?></p><?php endif; ?>
          <?php if ($operator['website']): ?><p class="side-card__body"><a href="<?= h(external_url($operator['website'])) ?>" target="_blank" rel="noopener">Website</a></p><?php endif; ?>
          <?php if ($operator['trip_advisor_link']): ?><p class="side-card__body"><a href="<?= h(external_url($operator['trip_advisor_link'])) ?>" target="_blank" rel="noopener">TripAdvisor</a></p><?php endif; ?>
        </div>

        <div class="side-card">
          <p class="side-card__title"><?= render_nav_glyph('tag') ?>At a glance</p>
          <?php if ($operator['office_location']): ?><p class="side-card__body"><strong>Location:</strong> <?= h($operator['office_location']) ?></p><?php endif; ?>
          <?php if ($operator['years_experience'] !== null): ?><p class="side-card__body"><strong>Experience:</strong> <?= (int) $operator['years_experience'] ?> years</p><?php endif; ?>
          <?php if ($operator['country_name']): ?><p class="side-card__body"><strong>Destination:</strong> <?= h($operator['country_name']) ?></p><?php endif; ?>
          <?php if ($operator['member_of']): ?><p class="side-card__body"><strong>Member of:</strong> <?= h($operator['member_of']) ?></p><?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
