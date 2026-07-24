<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$categorySlug = $_GET['category'] ?? '';
$category = null;

if ($categorySlug !== '') {
    $stmt = db()->prepare('SELECT * FROM trip_tour_categories WHERE slug = ?');
    $stmt->execute([$categorySlug]);
    $category = $stmt->fetch();
}

$budget = $_GET['budget'] ?? '';
$daysBucket = $_GET['days'] ?? '';

$where = ["t.status = 'published'"];
$params = [];

if ($category) {
    $where[] = 't.category_id = ?';
    $params[] = $category['id'];
}
if (in_array($budget, ['Luxury', 'Mid-Range', 'Budget'], true)) {
    $where[] = 't.budget_type = ?';
    $params[] = $budget;
}
if ($daysBucket === '1-3') {
    $where[] = 't.days BETWEEN 1 AND 3';
} elseif ($daysBucket === '4-7') {
    $where[] = 't.days BETWEEN 4 AND 7';
} elseif ($daysBucket === '8+') {
    $where[] = 't.days >= 8';
}

$sql = 'SELECT t.id, t.title, t.budget_type, t.price, t.discount_percent, t.days, t.short_overview, c.name AS category_name
    FROM trip_tours t
    JOIN trip_tour_categories c ON c.id = t.category_id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY t.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll();

$allCategories = db()->query('SELECT id, name, slug FROM trip_tour_categories ORDER BY sort_order, name')->fetchAll();

$pageHeading = $category ? $category['name'] : 'Trip Tours';
$page_title = $pageHeading . ' - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Trip Tours</p>
    <h1 class="page-header__title"><?= h($pageHeading) ?></h1>
    <?php if (!$category): ?>
      <p class="page-header__lead">Island getaways, camping trips, boat cruises, city breaks and more -- browse every published trip tour, or filter by category, budget and length below.</p>
    <?php endif; ?>
  </div>
</header>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Itineraries</p>
      <h2 class="section__title"><?= h($category ? $category['name'] . ' Tours' : 'All Trip Tours') ?></h2>
    </div>

    <form class="filter-bar" method="get">
      <?php if ($categorySlug !== ''): ?><input type="hidden" name="category" value="<?= h($categorySlug) ?>"><?php endif; ?>
      <div class="filter-bar__field">
        <label for="f-category"><?= render_nav_glyph('tag') ?>Category</label>
        <select id="f-category" name="category">
          <option value="">Any category</option>
          <?php foreach ($allCategories as $c): ?>
            <option value="<?= h($c['slug']) ?>" <?= $categorySlug === $c['slug'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-bar__field">
        <label for="f-budget"><?= render_nav_glyph('tag') ?>Budget</label>
        <select id="f-budget" name="budget">
          <option value="">Any budget</option>
          <?php foreach (['Luxury', 'Mid-Range', 'Budget'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $budget === $opt ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-bar__field">
        <label for="f-days"><?= render_nav_glyph('calendar') ?>Number of Days</label>
        <select id="f-days" name="days">
          <option value="">Any length</option>
          <option value="1-3" <?= $daysBucket === '1-3' ? 'selected' : '' ?>>1–3 days</option>
          <option value="4-7" <?= $daysBucket === '4-7' ? 'selected' : '' ?>>4–7 days</option>
          <option value="8+" <?= $daysBucket === '8+' ? 'selected' : '' ?>>8+ days</option>
        </select>
      </div>
      <button type="submit" class="btn btn--dark">Filter</button>
    </form>

    <?php if (!$tours): ?>
      <p class="empty-note">No trip tours match those filters yet. <a href="<?= h(url('/quote.php?type=safari')) ?>">Tell us what you're planning</a> and we'll build an itinerary for you.</p>
    <?php else: ?>
      <div class="card-grid" style="margin-top:28px;">
        <?php foreach ($tours as $tour): ?>
          <a href="<?= h(url('/trip-tour.php?id=' . $tour['id'])) ?>" class="tour-card">
            <div class="tour-card__media">
              <?php if ($cover = get_cover_image('trip_tour', $tour['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?>
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
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
