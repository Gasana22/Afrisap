<?php
declare(strict_types=1);

$safariCategories = db()->query("SELECT name, slug FROM tour_categories WHERE menu_group = 'safari' ORDER BY sort_order")->fetchAll();
$tripCategories = db()->query("SELECT name, slug FROM tour_categories WHERE menu_group = 'trip' ORDER BY sort_order")->fetchAll();
$experienceTypes = db()->query('SELECT name, slug FROM experience_types ORDER BY name')->fetchAll();
$activitiesNav = db()->query('SELECT id, name FROM activities ORDER BY name')->fetchAll();

$pageTitle = $page_title ?? 'Safarisap — Explore. Experience. Belong.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="Safarisap — East African safari, cultural and adventure tours. Explore. Experience. Belong.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(url('/assets/css/site.css')) ?>">
</head>
<body class="site">

<div class="utility-bar">
  <div class="wrap">
    <div>24/7 Support · <a href="tel:+256393246926">+256 393 246 926</a> · WhatsApp <a href="https://wa.me/256775328952">+256 775 328 952</a> · <a href="mailto:info@safarisap.com">info@safarisap.com</a></div>
    <div class="utility-bar__branches">Branches: Kampala · Nairobi · Addis Ababa · London</div>
  </div>
</div>

<nav class="site-nav">
  <div class="wrap">
    <a href="<?= h(url('/index.php')) ?>" class="site-nav__brand">Safari<span>sap</span></a>

    <button class="nav-toggle" aria-label="Toggle menu" onclick="document.querySelector('.site-nav__links').classList.toggle('is-open')">&#9776;</button>

    <ul class="site-nav__links">
      <li>
        <button>Safari Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($safariCategories as $cat): ?>
            <a href="<?= h(url('/tours.php?category=' . $cat['slug'])) ?>"><?= h($cat['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>Experiential Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($experienceTypes as $type): ?>
            <a href="<?= h(url('/experiences.php?type=' . $type['slug'])) ?>"><?= h($type['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>Trip Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($tripCategories as $cat): ?>
            <a href="<?= h(url('/tours.php?category=' . $cat['slug'])) ?>"><?= h($cat['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li><a href="<?= h(url('/tours.php?category=school-trips')) ?>">School Trips</a></li>
      <li>
        <button>Activities <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($activitiesNav as $activity): ?>
            <a href="<?= h(url('/activity.php?id=' . $activity['id'])) ?>"><?= h($activity['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>About Us <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <a href="<?= h(url('/about.php')) ?>">About</a>
          <a href="<?= h(url('/quote.php?type=safari')) ?>">Safari Quote</a>
          <a href="<?= h(url('/quote.php?type=experiential')) ?>">Experiential Quote</a>
          <a href="<?= h(url('/agents.php')) ?>">Join the Agent Pool</a>
          <a href="<?= h(url('/careers.php')) ?>">Careers</a>
          <a href="<?= h(url('/blog.php')) ?>">Blogs</a>
          <a href="<?= h(url('/travel-tips.php')) ?>">Uganda Travel Tips</a>
          <a href="<?= h(url('/contact.php')) ?>">Contact Us</a>
          <a href="<?= h(url('/gallery.php')) ?>">Gallery</a>
        </div>
      </li>
      <li><a href="<?= h(url('/quote.php?type=safari')) ?>" class="site-nav__cta">Get a Quote</a></li>
    </ul>
  </div>
</nav>
