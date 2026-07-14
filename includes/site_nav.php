<?php
declare(strict_types=1);
/**
 * Rendered inside render_site_nav()'s own function scope (see site_header.php)
 * so its loop variables ($nav_*) can never collide with a page-level variable
 * of the same name in whatever page required site_header.php.
 */
?>
<nav class="site-nav">
  <div class="wrap">
    <a href="<?= h(url('/')) ?>" class="site-nav__brand">Safari<span>sap</span></a>

    <button class="nav-toggle" aria-label="Toggle menu" onclick="document.querySelector('.site-nav__links').classList.toggle('is-open')">&#9776;</button>

    <ul class="site-nav__links">
      <li>
        <button>Safari Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($nav_safari_categories as $nav_category): ?>
            <a href="<?= h(url('/tours.php?category=' . $nav_category['slug'])) ?>"><?= h($nav_category['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>Experiential Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($nav_experience_types as $nav_type): ?>
            <a href="<?= h(url('/experiences.php?type=' . $nav_type['slug'])) ?>"><?= h($nav_type['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>Trip Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($nav_trip_categories as $nav_category): ?>
            <a href="<?= h(url('/tours.php?category=' . $nav_category['slug'])) ?>"><?= h($nav_category['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li><a href="<?= h(url('/tours.php?category=school-trips')) ?>">School Trips</a></li>
      <li>
        <button>Activities <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($nav_activities as $nav_activity): ?>
            <a href="<?= h(url('/activity.php?id=' . $nav_activity['id'])) ?>"><?= h($nav_activity['name']) ?></a>
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