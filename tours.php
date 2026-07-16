<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$categorySlug = $_GET['category'] ?? '';
$category = null;
$categoryPage = null;

$group = $_GET['group'] ?? '';
if (!in_array($group, ['safari', 'trip'], true)) {
    $group = '';
}

if ($categorySlug !== '') {
    $stmt = db()->prepare('SELECT * FROM tour_categories WHERE slug = ?');
    $stmt->execute([$categorySlug]);
    $category = $stmt->fetch();

    if ($category) {
        $cpStmt = db()->prepare('SELECT * FROM category_pages WHERE category_id = ?');
        $cpStmt->execute([$category['id']]);
        $categoryPage = $cpStmt->fetch();
    }
}

// Filters: Budget, Country, Days, Destination -- explicitly no keyword search, per spec.
$budget = $_GET['budget'] ?? '';
$countryId = (int) ($_GET['country'] ?? 0);
$daysBucket = $_GET['days'] ?? '';
$destinationId = (int) ($_GET['destination'] ?? 0);

$where = ["t.status = 'published'"];
$params = [];

if ($category) {
    $where[] = '(t.category_id = ? OR EXISTS (SELECT 1 FROM tour_extra_categories tec WHERE tec.tour_id = t.id AND tec.category_id = ?))';
    $params[] = $category['id'];
    $params[] = $category['id'];
} elseif ($group === 'safari') {
    $where[] = "c.menu_group = 'safari'";
} elseif ($group === 'trip') {
    $where[] = "c.menu_group IN ('trip', 'school')";
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
if ($destinationId) {
    $where[] = 'EXISTS (SELECT 1 FROM tour_destinations td JOIN destinations d ON d.id = td.destination_id WHERE td.tour_id = t.id AND d.id = ' . (int) $destinationId .
        ($countryId ? ' AND d.country_id = ' . (int) $countryId : '') . ')';
} elseif ($countryId) {
    // Matches either a tagged destination in that country, or the tour's own
    // explicit Countries selection (admin/tours/form.php) -- some tours cover
    // a country without a specific named destination attached yet.
    $where[] = '(EXISTS (SELECT 1 FROM tour_destinations td JOIN destinations d ON d.id = td.destination_id WHERE td.tour_id = t.id AND d.country_id = ' . (int) $countryId . ')' .
        ' OR EXISTS (SELECT 1 FROM tour_countries tc WHERE tc.tour_id = t.id AND tc.country_id = ' . (int) $countryId . '))';
}

$sql = 'SELECT t.id, t.title, t.budget_type, t.price, t.discount_percent, t.days, t.short_overview, c.name AS category_name
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY t.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll();

// National parks featured across the tours currently listed, so a filtered
// or empty result still points the visitor at the parks these tours visit.
$tourParks = [];
if ($tours) {
    $tourIds = array_column($tours, 'id');
    $placeholders = implode(',', array_fill(0, count($tourIds), '?'));
    $parksStmt = db()->prepare("SELECT DISTINCT d.id, d.name, c.name AS country_name
        FROM tour_destinations td
        JOIN destinations d ON d.id = td.destination_id
        JOIN countries c ON c.id = d.country_id
        WHERE td.tour_id IN ($placeholders)
        ORDER BY d.name");
    $parksStmt->execute($tourIds);
    $tourParks = $parksStmt->fetchAll();
}

$categoryGallery = $category ? get_media('category_page', $category['id']) : [];
$categoryParks = [];
if ($categoryPage) {
    $parkStmt = db()->prepare('SELECT d.id, d.name FROM category_page_parks cpp JOIN destinations d ON d.id = cpp.destination_id WHERE cpp.category_page_id = ?');
    $parkStmt->execute([$categoryPage['id']]);
    $categoryParks = $parkStmt->fetchAll();
}

$groupTitle = $group === 'trip' ? 'Trip Tours' : ($group === 'safari' ? 'Safari Tours' : 'Safari Tours');
$page_title = ($category ? $category['name'] : $groupTitle) . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow"><?= h($groupTitle) ?></p>
    <h1 class="page-header__title"><?= h($category ? $category['name'] : $groupTitle) ?></h1>
    <?php if ($categoryPage && $categoryPage['brief_overview']): ?>
      <p class="page-header__lead"><?= h($categoryPage['brief_overview']) ?></p>
    <?php elseif (!$category): ?>
      <p class="page-header__lead">Gorilla and chimpanzee trekking, wildlife drives, birding and combined East Africa itineraries -- browse every published safari, or filter by budget, country, length and destination below.</p>
    <?php endif; ?>
  </div>
</header>

<?php if ($categoryPage): ?>
<section class="section">
  <div class="wrap">
    <div class="detail-grid">
      <div class="detail-main">
        <?php if ($categoryPage['detailed_overview']): ?>
          <h2 class="detail-heading">Overview</h2>
          <p class="detail-text"><?= nl2br(h($categoryPage['detailed_overview'])) ?></p>
        <?php endif; ?>
        <?php if ($categoryPage['highlights']): ?>
          <h2 class="detail-heading">Highlights</h2>
          <p class="detail-text"><?= nl2br(h($categoryPage['highlights'])) ?></p>
        <?php endif; ?>
        <?php if ($categoryPage['unique_about']): ?>
          <h2 class="detail-heading">What makes it unique</h2>
          <p class="detail-text"><?= nl2br(h($categoryPage['unique_about'])) ?></p>
        <?php endif; ?>
        <?php if ($categoryPage['gorilla_permit_info']): ?>
          <h2 class="detail-heading">Permit information</h2>
          <p class="detail-text"><?= nl2br(h($categoryPage['gorilla_permit_info'])) ?></p>
        <?php endif; ?>
        <?php if ($categoryGallery): ?>
          <h2 class="detail-heading">Gallery</h2>
          <div class="gallery-strip">
            <?php foreach ($categoryGallery as $img): ?>
              <img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? '') ?>" loading="lazy">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <aside class="detail-side">
        <?php if ($categoryPage['when_to_visit']): ?>
          <div class="side-card">
            <p class="side-card__title"><?= render_nav_glyph('calendar') ?>When to visit</p>
            <p class="side-card__body"><?= nl2br(h($categoryPage['when_to_visit'])) ?></p>
          </div>
        <?php endif; ?>
        <?php if ($categoryParks): ?>
          <div class="side-card">
            <p class="side-card__title"><?= render_nav_glyph('pin') ?>National parks</p>
            <?php foreach ($categoryParks as $park): ?>
              <a href="<?= h(url('/destination.php?id=' . $park['id'])) ?>" class="side-card__link"><?= h($park['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($categoryPage['more_activities']): ?>
          <div class="side-card">
            <p class="side-card__title"><?= render_nav_glyph('sliders') ?>More activities available</p>
            <p class="side-card__body"><?= nl2br(h($categoryPage['more_activities'])) ?></p>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Itineraries</p>
      <h2 class="section__title"><?= h($category ? $category['name'] . ' Tours' : 'All ' . $groupTitle) ?></h2>
    </div>

    <form class="filter-bar" method="get">
      <?php if ($categorySlug !== ''): ?><input type="hidden" name="category" value="<?= h($categorySlug) ?>"><?php endif; ?>
      <?php if ($group !== ''): ?><input type="hidden" name="group" value="<?= h($group) ?>"><?php endif; ?>
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
        <label for="f-country"><?= render_nav_glyph('compass') ?>Country</label>
        <select id="f-country" name="country">
          <option value="">Any country</option>
          <?php foreach (db()->query('SELECT id, name FROM countries ORDER BY name')->fetchAll() as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $countryId === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
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
      <p class="empty-note">No tours match those filters yet. <a href="<?= h(url('/quote.php?type=safari')) ?>">Tell us what you're planning</a> and we'll build an itinerary for you.</p>
    <?php else: ?>
      <div class="card-grid" style="margin-top:28px;">
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
    <?php endif; ?>

    <?php if ($tourParks): ?>
      <div class="section__header" style="margin:36px 0 20px;">
        <p class="section__eyebrow">On The Ground</p>
        <h2 class="section__title" style="font-size:20px;">National parks featured in these tours</h2>
      </div>
      <div class="tile-rail">
        <?php foreach ($tourParks as $park): ?>
          <a href="<?= h(url('/destination.php?id=' . $park['id'])) ?>" class="tile">
            <h3 class="tile__title"><?= h($park['name']) ?></h3>
            <p class="tile__meta"><?= h($park['country_name']) ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
