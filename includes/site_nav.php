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
            <a href="<?= h(url('/tours.php?category=' . $nav_category['slug'])) ?>"><?= render_nav_glyph(nav_icon_for($nav_category['name'])) ?><span><?= h($nav_category['name']) ?></span><span class="mega-menu__count"><?= (int) ($nav_category_tour_counts[$nav_category['id']] ?? 0) ?></span></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>Experiential Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($nav_experience_types as $nav_type): ?>
            <a href="<?= h(url('/experiences.php?type=' . $nav_type['slug'])) ?>"><?= render_nav_glyph(nav_icon_for($nav_type['name'])) ?><span><?= h($nav_type['name']) ?></span></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>Trip Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($nav_trip_categories as $nav_category): ?>
            <a href="<?= h(url('/tours.php?category=' . $nav_category['slug'])) ?>"><?= render_nav_glyph(nav_icon_for($nav_category['name'])) ?><span><?= h($nav_category['name']) ?></span><span class="mega-menu__count"><?= (int) ($nav_category_tour_counts[$nav_category['id']] ?? 0) ?></span></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>Activities <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <?php foreach ($nav_activities as $nav_activity): ?>
            <a href="<?= h(url('/activity.php?id=' . $nav_activity['id'])) ?>"><?= render_nav_glyph(nav_icon_for($nav_activity['name'])) ?><span><?= h($nav_activity['name']) ?></span></a>
          <?php endforeach; ?>
        </div>
      </li>
      <li>
        <button>About Us <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu">
          <a href="<?= h(url('/about.php')) ?>"><?= render_nav_glyph('compass') ?><span>About</span></a>
          <a href="<?= h(url('/quote.php?type=safari')) ?>"><?= render_nav_glyph('tag') ?><span>Safari Quote</span></a>
          <a href="<?= h(url('/quote.php?type=experiential')) ?>"><?= render_nav_glyph('tag') ?><span>Experiential Quote</span></a>
          <a href="<?= h(url('/agents.php')) ?>"><?= render_nav_glyph('users') ?><span>Join the Agent Pool</span></a>
          <a href="<?= h(url('/careers.php')) ?>"><?= render_nav_glyph('briefcase') ?><span>Careers</span></a>
          <a href="<?= h(url('/blog.php')) ?>"><?= render_nav_glyph('doc') ?><span>Blogs</span></a>
          <a href="<?= h(url('/travel-tips.php')) ?>"><?= render_nav_glyph('pin') ?><span>Travel Tips</span></a>
          <a href="<?= h(url('/contact.php')) ?>"><?= render_nav_glyph('mail') ?><span>Contact Us</span></a>
          <a href="<?= h(url('/gallery.php')) ?>"><?= render_nav_glyph('image') ?><span>Gallery</span></a>
        </div>
      </li>
      <li>
        <button>Specialised Tours <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu mega-menu--narrow">
          <a href="<?= h(url('/scheduled-tours.php')) ?>"><?= render_nav_glyph('calendar') ?><span>Scheduled Tours</span></a>
          <a href="<?= h(url('/tours.php?category=group-tours')) ?>"><?= render_nav_glyph('users') ?><span>Group Tours</span></a>
          <a href="<?= h(url('/create-your-own-tour.php')) ?>"><?= render_nav_glyph('sliders') ?><span>Create Your Own Tour</span></a>
          <a href="<?= h(url('/virtual-experience.php')) ?>"><?= render_nav_glyph('video') ?><span>Virtual Experience</span></a>
        </div>
      </li>
      <li>
        <button>Car Rental <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu mega-menu--narrow">
          <a href="<?= h(url('/car-rental.php?type=safari-4x4-landcruiser')) ?>"><?= render_nav_glyph('car') ?><span>Safari 4X4 Landcruiser</span></a>
          <a href="<?= h(url('/car-rental.php?type=vans')) ?>"><?= render_nav_glyph('car') ?><span>VANs</span></a>
          <a href="<?= h(url('/car-rental.php?type=airport-transfer')) ?>"><?= render_nav_glyph('car') ?><span>Airport Transfer</span></a>
          <a href="<?= h(url('/car-rental.php?type=luxury-cars')) ?>"><?= render_nav_glyph('car') ?><span>Luxury Cars</span></a>
        </div>
      </li>
      <li>
        <button>Flight Booking <span class="site-nav__caret">&#9662;</span></button>
        <div class="mega-menu mega-menu--narrow">
          <a href="<?= h(url('/flight-booking.php?type=internal-flights')) ?>"><?= render_nav_glyph('plane') ?><span>Internal Flights</span></a>
          <a href="<?= h(url('/flight-booking.php?type=chartered-flights')) ?>"><?= render_nav_glyph('plane') ?><span>Chartered Flights</span></a>
        </div>
      </li>
      <li><a href="<?= h(url('/operators.php')) ?>" class="site-nav__cta">Tour Operators <span class="site-nav__cta-count"><?= $nav_operator_count ?></span></a></li>
    </ul>
  </div>
</nav>
