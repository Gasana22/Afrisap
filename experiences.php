<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$typeSlug = $_GET['type'] ?? '';
$type = null;

if ($typeSlug !== '') {
    $stmt = db()->prepare('SELECT * FROM experience_types WHERE slug = ?');
    $stmt->execute([$typeSlug]);
    $type = $stmt->fetch();
}

$allTypes = db()->query('SELECT * FROM experience_types ORDER BY name')->fetchAll();

$page_title = ($type ? $type['name'] : 'Experiential Tours') . ' - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Experiential Tours</p>
    <h1 class="page-header__title"><?= h($type ? $type['name'] : 'Experiential Tours') ?></h1>
    <p class="page-header__lead">Not wildlife -- everyday Uganda. Culture by tribe, working farms, local sport, manufacturing, and neighbourhood life.</p>
  </div>
</header>

<?php if (!$type): ?>
<section class="section">
  <div class="wrap">
    <div class="experience-grid">
      <?php foreach ($allTypes as $i => $t): ?>
        <a href="<?= h(url('/experiences.php?type=' . $t['slug'])) ?>" class="experience-tile">
          <div class="experience-tile__title"><?= h(str_replace(' Experience', '', $t['name'])) ?></div>
          <div class="experience-tile__number"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:44px;">
      <p class="section__eyebrow">How It Works</p>
      <h2 class="section__title">Three steps into everyday Uganda</h2>
    </div>
    <div class="why-grid">
      <div class="why-item">
        <div class="why-item__icon"><?= render_nav_glyph('compass') ?></div>
        <h3 class="why-item__title">01. Pick a type</h3>
        <p class="why-item__body">Choose the world you want in: a tribe's culture, a working farm, local sport, a factory floor, or a neighbourhood.</p>
      </div>
      <div class="why-item">
        <div class="why-item__icon"><?= render_nav_glyph('pin') ?></div>
        <h3 class="why-item__title">02. Choose a destination</h3>
        <p class="why-item__body">Each type has its own set of real places and communities -- browse them and see what they offer.</p>
      </div>
      <div class="why-item">
        <div class="why-item__icon"><?= render_nav_glyph('briefcase') ?></div>
        <h3 class="why-item__title">03. Book with a local provider</h3>
        <p class="why-item__body">Every experience is run by a named service provider on the ground, not a call centre.</p>
      </div>
    </div>
  </div>
</section>
<?php else: ?>

<?php
$destinationId = (int) ($_GET['destination'] ?? 0);

$typeDestinations = db()->prepare('SELECT id, name, location FROM experience_destinations WHERE experience_type_id = ? ORDER BY name');
$typeDestinations->execute([$type['id']]);
$typeDestinations = $typeDestinations->fetchAll();

$where = ["et.status = 'published'", 'et.experience_type_id = ?'];
$params = [$type['id']];
if ($destinationId) {
    $where[] = 'EXISTS (SELECT 1 FROM experience_tour_destinations etd WHERE etd.experience_tour_id = et.id AND etd.experience_destination_id = ?)';
    $params[] = $destinationId;
}
$stmt = db()->prepare('SELECT et.id, et.title, et.price, et.discount_percent, et.days, et.short_overview
    FROM experience_tours et
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY et.created_at DESC');
$stmt->execute($params);
$tours = $stmt->fetchAll();
?>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow">Itineraries</p>
      <h2 class="section__title"><?= h($type['name']) ?> Tours</h2>
    </div>

    <?php if ($typeDestinations): ?>
      <form class="filter-bar" method="get" style="grid-template-columns: 1fr auto;">
        <input type="hidden" name="type" value="<?= h($typeSlug) ?>">
        <div class="filter-bar__field">
          <label for="f-destination"><?= render_nav_glyph('pin') ?>Destination</label>
          <select id="f-destination" name="destination">
            <option value="">Any destination</option>
            <?php foreach ($typeDestinations as $dest): ?>
              <option value="<?= (int) $dest['id'] ?>" <?= $destinationId === (int) $dest['id'] ? 'selected' : '' ?>><?= h($dest['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn--dark">Search</button>
      </form>
    <?php endif; ?>

    <?php if (!$tours): ?>
      <p class="empty-note" style="margin-top:24px;">No <?= h(strtolower($type['name'])) ?> tours match yet. <a href="<?= h(url('/quote.php?type=experiential')) ?>">Tell us what you're picturing</a> and we'll build one.</p>
    <?php else: ?>
      <div class="card-grid" style="margin-top:28px;">
        <?php foreach ($tours as $tour): ?>
          <a href="<?= h(url('/experience-tour.php?id=' . $tour['id'])) ?>" class="tour-card">
            <div class="tour-card__media">
              <?php if ($cover = get_cover_image('experience_tour', $tour['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?>
              <span class="tour-card__badge"><?= h($type['name']) ?></span>
            </div>
            <div class="tour-card__body">
              <p class="tour-card__meta"><?= (int) $tour['days'] ?> days</p>
              <h3 class="tour-card__title"><?= h($tour['title']) ?></h3>
              <p class="tour-card__overview"><?= h(mb_strimwidth((string) $tour['short_overview'], 0, 110, '…')) ?></p>
              <div class="tour-card__footer">
                <div class="tour-card__price">
                  <span class="tour-card__price-label">Starting from</span>$<?= number_format((float) $tour['price'] * (1 - (float) $tour['discount_percent'] / 100), 0) ?><span class="tour-card__price-unit">/ person</span>
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

<section class="section">
  <div class="wrap">
    <div class="section__header" style="margin-bottom:28px;">
      <p class="section__eyebrow"><?= h(str_replace(' Experience', '', $type['name'])) ?> Destinations</p>
      <h2 class="section__title section__title--sm">Browse by <?= h(strtolower(str_replace(' Experience', '', $type['name']))) === 'cultural' ? 'tribe' : 'destination' ?></h2>
    </div>
    <?php if (!$typeDestinations): ?>
      <p class="empty-note">Destinations for this experience type are being added.</p>
    <?php else: ?>
      <div class="tile-rail">
        <?php foreach ($typeDestinations as $dest): ?>
          <a href="<?= h(url('/experience-destination.php?id=' . $dest['id'])) ?>" class="tile">
            <div class="tile__media"><?php if ($cover = get_cover_image('experience_destination', $dest['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?></div>
            <div class="tile__body">
              <h3 class="tile__title"><?= h($dest['name']) ?></h3>
              <?php if ($dest['location']): ?><p class="tile__meta"><?= h($dest['location']) ?></p><?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
