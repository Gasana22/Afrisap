<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/site_svg.php';
require_once __DIR__ . '/includes/media.php';

$featuredTours = db()->query("SELECT t.id, t.title, t.budget_type, t.price, t.discount_percent, t.days, t.short_overview,
        c.name AS category_name
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    WHERE t.status = 'published'
    ORDER BY t.created_at DESC
    LIMIT 6")->fetchAll();

$destinations = db()->query("SELECT d.id, d.name, c.name AS country_name
    FROM destinations d
    JOIN countries c ON c.id = d.country_id
    WHERE d.is_featured = 1
    ORDER BY d.created_at DESC
    LIMIT 8")->fetchAll();

$experienceTypes = db()->query('SELECT id, name, slug, image_path, short_description FROM experience_types ORDER BY name')->fetchAll();

$partners = db()->query('SELECT id, company_name, logo_path FROM tour_operators ORDER BY company_name LIMIT 12')->fetchAll();

$siteSettings = db()->query('SELECT * FROM site_settings WHERE id = 1')->fetch() ?: [];
$heroEyebrowCountries = [
    ['label' => 'East Africa', 'href' => null],
    ['label' => 'Uganda', 'href' => 'tours.php?country=1'],
    ['label' => 'Kenya', 'href' => 'tours.php?country=2'],
    ['label' => 'Tanzania', 'href' => 'tours.php?country=3'],
    ['label' => 'Rwanda', 'href' => 'tours.php?country=4'],
    ['label' => 'DR Congo', 'href' => 'tours.php?country=7'],
];
$heroTitle = $siteSettings['hero_title'] ?? '' ?: 'Explore. Experience. Belong.';
$heroSubtitle = $siteSettings['hero_subtitle'] ?? '' ?: "Gorilla treks through misty forest, a boat cruise past hippos, a drumming circle in a Buganda village. Safarisap plans East Africa on your terms.";
$heroBackground = $siteSettings['hero_background_path'] ?? null;
$heroSlides = get_media('hero_slideshow', 1);

$statSafariTours = (int) db()->query("SELECT COUNT(*) FROM tours t JOIN tour_categories c ON c.id = t.category_id WHERE c.menu_group = 'safari' AND t.status = 'published'")->fetchColumn();
$statExperienceTours = (int) db()->query("SELECT COUNT(*) FROM experience_tours WHERE status = 'published'")->fetchColumn();
$statActivities = (int) db()->query('SELECT COUNT(*) FROM activities')->fetchColumn();
$statTripTours = (int) db()->query("SELECT COUNT(*) FROM tours t JOIN tour_categories c ON c.id = t.category_id WHERE c.menu_group IN ('trip', 'school') AND t.status = 'published'")->fetchColumn();

$page_title = 'Safarisap — Explore. Experience. Belong.';
require __DIR__ . '/includes/site_header.php';
?>

<header class="hero<?= ($heroSlides || $heroBackground) ? ' hero--photo' : '' ?>">
  <?php if ($heroSlides): ?>
    <div class="hero__slideshow">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <img class="hero__slide<?= $i === 0 ? ' is-active' : '' ?>" src="<?= h(url('/' . $slide['file_path'])) ?>" alt="">
      <?php endforeach; ?>
    </div>
  <?php elseif ($heroBackground): ?>
    <img class="hero__photo" src="<?= h(url('/' . $heroBackground)) ?>" alt="">
  <?php else: ?>
    <?= render_hero_illustration() ?>
  <?php endif; ?>
  <div class="hero__countries">
    <?php foreach ($heroEyebrowCountries as $country): ?>
      <?php if ($country['href']): ?>
        <a href="<?= h(url('/' . $country['href'])) ?>" class="hero__country-chip"><?= h($country['label']) ?></a>
      <?php else: ?>
        <span class="hero__country-chip"><?= h($country['label']) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="wrap hero__content">
    <h1 class="hero__title"><?= h($heroTitle) ?></h1>
    <p class="hero__subtitle"><?= h($heroSubtitle) ?></p>
    <div class="hero__actions">
      <a href="<?= h(url('/tours.php')) ?>" class="btn btn--primary btn--pill">Plan a Safari <span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
      <a href="<?= h(url('/experiences.php')) ?>" class="btn btn--outline btn--pill">Discover Experiences <span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
    </div>
  </div>
  <div class="hero__stats">
    <a href="<?= h(url('/tours.php?group=safari')) ?>" class="hero-stat">
      <span class="hero-stat__icon"><?= render_nav_glyph('binoculars') ?></span>
      <span class="hero-stat__text">
        <span class="hero-stat__label">Safari Tours</span>
        <span class="hero-stat__value"><?= $statSafariTours ?></span>
      </span>
    </a>
    <a href="<?= h(url('/experiences.php')) ?>" class="hero-stat">
      <span class="hero-stat__icon"><?= render_nav_glyph('mask') ?></span>
      <span class="hero-stat__text">
        <span class="hero-stat__label">Experiential Tours</span>
        <span class="hero-stat__value"><?= $statExperienceTours ?></span>
      </span>
    </a>
    <div class="hero-stat">
      <span class="hero-stat__icon"><?= render_nav_glyph('sliders') ?></span>
      <span class="hero-stat__text">
        <span class="hero-stat__label">Activities</span>
        <span class="hero-stat__value"><?= $statActivities ?></span>
      </span>
    </div>
    <a href="<?= h(url('/tours.php?group=trip')) ?>" class="hero-stat">
      <span class="hero-stat__icon"><?= render_nav_glyph('compass') ?></span>
      <span class="hero-stat__text">
        <span class="hero-stat__label">Trip Tours</span>
        <span class="hero-stat__value"><?= $statTripTours ?></span>
      </span>
    </a>
  </div>
</header>

<div class="search-band">
  <form class="wrap search-bar" action="<?= h(url('/tours.php')) ?>" method="get">
    <div class="search-bar__field">
      <label for="search-budget">Budget</label>
      <select id="search-budget" name="budget">
        <option value="">Any budget</option>
        <option value="Luxury">Luxury</option>
        <option value="Mid-Range">Mid-Range</option>
        <option value="Budget">Budget</option>
      </select>
    </div>
    <div class="search-bar__field">
      <label for="search-country">Country</label>
      <select id="search-country" name="country">
        <option value="">Any country</option>
        <?php foreach (db()->query('SELECT id, name FROM countries ORDER BY name')->fetchAll() as $country): ?>
          <option value="<?= (int) $country['id'] ?>"><?= h($country['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="search-bar__field">
      <label for="search-days">Number of Days</label>
      <select id="search-days" name="days">
        <option value="">Any length</option>
        <option value="1-3">1–3 days</option>
        <option value="4-7">4–7 days</option>
        <option value="8+">8+ days</option>
      </select>
    </div>
    <button type="submit" class="btn btn--dark">Search</button>
  </form>
</div>

<section class="section">
  <div class="wrap">
    <div class="section__header">
      <p class="section__eyebrow">Two Ways to Travel</p>
      <h2 class="section__title">Wildlife, or the world people live in</h2>
      <p class="section__lead">Safari tours take you into the parks. Experiential tours take you into daily life: a farm, a factory floor, a football pitch, a neighbourhood. Both are Uganda.</p>
    </div>
    <div class="doors">
      <a href="<?= h(url('/tours.php')) ?>" class="door door--safari">
        <p class="door__route">ROUTE 01 &mdash; INTO THE PARKS</p>
        <p class="door__eyebrow">Safari Tours</p>
        <h3 class="door__title">Gorillas, game drives &amp; the Rift Valley</h3>
        <p class="door__body">Gorilla and chimpanzee trekking, wildlife drives, birding, and combined East Africa itineraries.</p>
        <span class="btn btn--outline">Browse Safaris</span>
      </a>
      <a href="<?= h(url('/experiences.php')) ?>" class="door door--experiential">
        <p class="door__route">ROUTE 02 &mdash; INTO DAILY LIFE</p>
        <p class="door__eyebrow">Experiential Tours</p>
        <h3 class="door__title">Culture, farms, sport &amp; the everyday</h3>
        <p class="door__body">Cultural immersion by tribe, working farm visits, local sport, manufacturing tours, and ghetto experiences.</p>
        <span class="btn btn--outline">Browse Experiences</span>
      </a>
    </div>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header">
      <p class="section__eyebrow">Featured Itineraries</p>
      <h2 class="section__title">Recently added safaris</h2>
    </div>
    <?php if (!$featuredTours): ?>
      <p class="empty-note">Tours are being added. Check back soon, or <a href="<?= h(url('/quote.php?type=safari')) ?>">tell us what you're planning</a> and we'll build an itinerary around it.</p>
    <?php else: ?>
      <div class="card-grid">
        <?php foreach ($featuredTours as $tour): ?>
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
                  $<?= number_format((float) $tour['price'], 0) ?>
                  <?php if ((float) $tour['discount_percent'] > 0): ?><small><?= (float) $tour['discount_percent'] ?>% off</small><?php endif; ?>
                </div>
                <span class="btn btn--dark" style="padding:8px 14px;font-size:13px;">View</span>
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
    <div class="section__header">
      <p class="section__eyebrow">Explore by Destination</p>
      <h2 class="section__title">National parks &amp; game reserves</h2>
    </div>
    <?php if (!$destinations): ?>
      <p class="empty-note">Destinations are being added. Check back soon.</p>
    <?php else: ?>
      <div class="tile-rail">
        <?php foreach ($destinations as $dest): ?>
          <a href="<?= h(url('/destination.php?id=' . $dest['id'])) ?>" class="tile">
            <h3 class="tile__title"><?= h($dest['name']) ?></h3>
            <p class="tile__meta"><?= h($dest['country_name']) ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header">
      <p class="section__eyebrow">Experiential Tours</p>
      <h2 class="section__title">Five ways into everyday Uganda</h2>
    </div>
    <div class="experience-grid">
      <?php foreach ($experienceTypes as $type): ?>
        <a href="<?= h(url('/experiences.php?type=' . $type['slug'])) ?>" class="experience-tile<?= $type['image_path'] ? ' experience-tile--photo' : '' ?>">
          <?php if ($type['image_path']): ?>
            <img class="experience-tile__photo" src="<?= h(url('/' . $type['image_path'])) ?>" alt="" loading="lazy">
          <?php endif; ?>
          <div class="experience-tile__mark"><?= render_nav_glyph(nav_icon_for($type['name'])) ?></div>
          <div class="experience-tile__title"><?= h(str_replace(' Experience', '', $type['name'])) ?></div>
          <?php if ($type['short_description']): ?>
            <p class="experience-tile__desc"><?= h($type['short_description']) ?></p>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="ribbon-cta">
  <div class="wrap">
    <p class="ribbon-cta__text">Not seeing exactly what you want? Build your own itinerary.</p>
    <a href="<?= h(url('/create-your-own-tour.php')) ?>" class="btn btn--dark btn--pill">Create Your Own Tour <span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section__header">
      <p class="section__eyebrow">Why Safarisap</p>
      <h2 class="section__title">Planned by people who know the ground</h2>
    </div>
    <div class="why-grid">
      <div class="why-item">
        <div class="why-item__icon"><?= render_nav_glyph('briefcase') ?></div>
        <h3 class="why-item__title">Licensed operators, not middlemen</h3>
        <p class="why-item__body">Every tour is run by a named, contactable operator: you know exactly who's guiding you before you book.</p>
      </div>
      <div class="why-item">
        <div class="why-item__icon"><?= render_nav_glyph('compass') ?></div>
        <h3 class="why-item__title">Beyond the wildlife circuit</h3>
        <p class="why-item__body">We're one of the few platforms built around experiential tourism too: real farms, real neighbourhoods, real people.</p>
      </div>
      <div class="why-item">
        <div class="why-item__icon"><?= render_nav_glyph('pin') ?></div>
        <h3 class="why-item__title">Branches across the region</h3>
        <p class="why-item__body">Kampala, Nairobi, Addis Ababa and London, supporting you in your timezone, on the ground where it matters.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap">
    <div class="section__header">
      <p class="section__eyebrow">Who We Work With</p>
      <h2 class="section__title">Our Partners</h2>
    </div>
    <?php if (!$partners): ?>
      <p class="empty-note">Operator profiles are being added. Check back soon, or <a href="<?= h(url('/contact.php')) ?>">get in touch</a> if you run tours in East Africa and want to be listed.</p>
    <?php else: ?>
      <div class="partner-grid">
        <?php foreach ($partners as $partner): ?>
          <a href="<?= h(url('/operators.php')) ?>" class="partner-card">
            <?php if ($partner['logo_path']): ?>
              <img class="partner-card__logo" src="<?= h(url('/' . $partner['logo_path'])) ?>" alt="<?= h($partner['company_name']) ?>" loading="lazy">
            <?php else: ?>
              <span class="partner-card__name"><?= h($partner['company_name']) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
      <p class="section__more"><a href="<?= h(url('/operators.php')) ?>">See all tour operators &rarr;</a></p>
    <?php endif; ?>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2 class="cta-band__title">Not sure where to start?</h2>
    <p class="cta-band__body">Tell us what you're picturing and we'll put together a quote (safari, experiential, or a mix of both).</p>
    <div class="cta-band__actions">
      <a href="<?= h(url('/quote.php?type=safari')) ?>" class="btn btn--primary">Get a Safari Quote</a>
      <a href="<?= h(url('/quote.php?type=experiential')) ?>" class="btn btn--outline">Get an Experiential Quote</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
