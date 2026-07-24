<?php
declare(strict_types=1);

require_once __DIR__ . '/svg.php';
require_once __DIR__ . '/../../includes/site_svg.php';

$admin = current_admin();
$counts = [
    'countries' => (int) db()->query('SELECT COUNT(*) FROM countries')->fetchColumn(),
    'categories' => (int) db()->query('SELECT COUNT(*) FROM tour_categories')->fetchColumn(),
    'operators' => (int) db()->query('SELECT COUNT(*) FROM tour_operators')->fetchColumn(),
    'destinations' => (int) db()->query('SELECT COUNT(*) FROM destinations')->fetchColumn(),
    'tours' => (int) db()->query('SELECT COUNT(*) FROM tours')->fetchColumn(),
    'trip-tours' => (int) db()->query("SELECT COUNT(*) FROM tours t JOIN tour_categories c ON c.id = t.category_id WHERE c.menu_group IN ('trip', 'school')")->fetchColumn(),
    'activities' => (int) db()->query('SELECT COUNT(*) FROM activities')->fetchColumn(),
    'experience-types' => (int) db()->query('SELECT COUNT(*) FROM experience_types')->fetchColumn(),
    'providers' => (int) db()->query('SELECT COUNT(*) FROM service_providers')->fetchColumn(),
    'experience-destinations' => (int) db()->query('SELECT COUNT(*) FROM experience_destinations')->fetchColumn(),
    'experience-tours' => (int) db()->query('SELECT COUNT(*) FROM experience_tours')->fetchColumn(),
    'pages' => (int) db()->query('SELECT COUNT(*) FROM pages')->fetchColumn(),
    'careers' => (int) db()->query('SELECT COUNT(*) FROM careers')->fetchColumn(),
    'blog' => (int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn(),
    'bookings' => (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'tour-inquiries' => (int) db()->query("SELECT COUNT(*) FROM tour_itinerary_inquiries WHERE status = 'new'")->fetchColumn(),
    'quotes' => (int) db()->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'new'")->fetchColumn(),
    'car-rental' => (int) db()->query("SELECT COUNT(*) FROM car_rental_requests WHERE status = 'new'")->fetchColumn(),
    'flight-booking' => (int) db()->query("SELECT COUNT(*) FROM flight_booking_requests WHERE status = 'new'")->fetchColumn(),
    'custom-tours' => (int) db()->query("SELECT COUNT(*) FROM custom_tour_requests WHERE status = 'new'")->fetchColumn(),
    'virtual-experience-signups' => (int) db()->query('SELECT COUNT(*) FROM virtual_experience_signups')->fetchColumn(),
    'agents' => (int) db()->query("SELECT COUNT(*) FROM agents WHERE status = 'new'")->fetchColumn(),
    'career-applications' => (int) db()->query('SELECT COUNT(*) FROM career_applications')->fetchColumn(),
    'messages' => (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
];
$active_nav = $active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title ?? 'Admin') ?> · Safarisap Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(url('/assets/admin/css/admin.css')) ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar">
    <?= sidebar_field_svg() ?>
    <div class="sidebar__brand">
      <?= sidebar_mark_svg() ?>
      <div>
        <span class="sidebar__wordmark">Safarisap</span>
        <span class="sidebar__tagline">Explore. Experience. Belong.</span>
      </div>
    </div>
    <nav class="sidebar__nav">
      <div class="nav-group">
        <div class="nav-group__label">Core</div>
        <a class="nav-link<?= $active_nav === 'dashboard' ? ' is-active' : '' ?>" href="<?= h(url('/admin/index.php')) ?>"><?= render_nav_glyph('compass') ?>Dashboard</a>
      </div>
      <div class="nav-group">
        <div class="nav-group__label">Lookups</div>
        <a class="nav-link<?= $active_nav === 'countries' ? ' is-active' : '' ?>" href="<?= h(url('/admin/countries/index.php')) ?>">
          <?= render_nav_glyph('pin') ?>Countries <span class="nav-link__count"><?= $counts['countries'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'categories' ? ' is-active' : '' ?>" href="<?= h(url('/admin/categories/index.php')) ?>">
          <?= render_nav_glyph('tag') ?>Tour Categories <span class="nav-link__count"><?= $counts['categories'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'operators' ? ' is-active' : '' ?>" href="<?= h(url('/admin/operators/index.php')) ?>">
          <?= render_nav_glyph('briefcase') ?>Tour Operators <span class="nav-link__count"><?= $counts['operators'] ?></span>
        </a>
      </div>
      <div class="nav-group">
        <div class="nav-group__label">Safari</div>
        <a class="nav-link<?= $active_nav === 'destinations' ? ' is-active' : '' ?>" href="<?= h(url('/admin/destinations/index.php')) ?>">
          <?= render_nav_glyph('pin') ?>Destinations <span class="nav-link__count"><?= $counts['destinations'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'tours' ? ' is-active' : '' ?>" href="<?= h(url('/admin/tours/index.php')) ?>">
          <?= render_nav_glyph('compass') ?>Tours <span class="nav-link__count"><?= $counts['tours'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'trip-tours' ? ' is-active' : '' ?>" href="<?= h(url('/admin/tours/index.php?menu_group=trip')) ?>">
          <?= render_nav_glyph('pin') ?>Trip Tours <span class="nav-link__count"><?= $counts['trip-tours'] ?></span>
        </a>
      </div>
      <div class="nav-group">
        <div class="nav-group__label">Activities</div>
        <a class="nav-link<?= $active_nav === 'activities' ? ' is-active' : '' ?>" href="<?= h(url('/admin/activities/index.php')) ?>">
          <?= render_nav_glyph('sliders') ?>Activities <span class="nav-link__count"><?= $counts['activities'] ?></span>
        </a>
      </div>
      <div class="nav-group">
        <div class="nav-group__label">Experiential</div>
        <a class="nav-link<?= $active_nav === 'experience-types' ? ' is-active' : '' ?>" href="<?= h(url('/admin/experience-types/index.php')) ?>">
          <?= render_nav_glyph('image') ?>Experience Types <span class="nav-link__count"><?= $counts['experience-types'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'providers' ? ' is-active' : '' ?>" href="<?= h(url('/admin/providers/index.php')) ?>">
          <?= render_nav_glyph('briefcase') ?>Service Providers <span class="nav-link__count"><?= $counts['providers'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'experience-destinations' ? ' is-active' : '' ?>" href="<?= h(url('/admin/experience-destinations/index.php')) ?>">
          <?= render_nav_glyph('pin') ?>Experience Destinations <span class="nav-link__count"><?= $counts['experience-destinations'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'experience-tours' ? ' is-active' : '' ?>" href="<?= h(url('/admin/experience-tours/index.php')) ?>">
          <?= render_nav_glyph('mask') ?>Experience Tours <span class="nav-link__count"><?= $counts['experience-tours'] ?></span>
        </a>
      </div>
      <div class="nav-group">
        <div class="nav-group__label">About Us</div>
        <a class="nav-link<?= $active_nav === 'pages' ? ' is-active' : '' ?>" href="<?= h(url('/admin/pages/index.php')) ?>">
          <?= render_nav_glyph('doc') ?>Pages <span class="nav-link__count"><?= $counts['pages'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'careers' ? ' is-active' : '' ?>" href="<?= h(url('/admin/careers/index.php')) ?>">
          <?= render_nav_glyph('briefcase') ?>Careers <span class="nav-link__count"><?= $counts['careers'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'blog' ? ' is-active' : '' ?>" href="<?= h(url('/admin/blog/index.php')) ?>">
          <?= render_nav_glyph('doc') ?>Blog <span class="nav-link__count"><?= $counts['blog'] ?></span>
        </a>
      </div>
      <div class="nav-group">
        <div class="nav-group__label">Inbox</div>
        <a class="nav-link<?= $active_nav === 'bookings' ? ' is-active' : '' ?>" href="<?= h(url('/admin/bookings/index.php')) ?>">
          <?= render_nav_glyph('calendar') ?>Bookings <span class="nav-link__count"><?= $counts['bookings'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'tour-inquiries' ? ' is-active' : '' ?>" href="<?= h(url('/admin/tour-inquiries/index.php')) ?>">
          <?= render_nav_glyph('tag') ?>Tour Itinerary Inquiries <span class="nav-link__count"><?= $counts['tour-inquiries'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'quotes' ? ' is-active' : '' ?>" href="<?= h(url('/admin/quotes/index.php')) ?>">
          <?= render_nav_glyph('tag') ?>Quote Requests <span class="nav-link__count"><?= $counts['quotes'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'car-rental' ? ' is-active' : '' ?>" href="<?= h(url('/admin/car-rental/index.php')) ?>">
          <?= render_nav_glyph('car') ?>Car Rental Requests <span class="nav-link__count"><?= $counts['car-rental'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'flight-booking' ? ' is-active' : '' ?>" href="<?= h(url('/admin/flight-booking/index.php')) ?>">
          <?= render_nav_glyph('plane') ?>Flight Booking Requests <span class="nav-link__count"><?= $counts['flight-booking'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'custom-tours' ? ' is-active' : '' ?>" href="<?= h(url('/admin/custom-tours/index.php')) ?>">
          <?= render_nav_glyph('sliders') ?>Custom Tour Requests <span class="nav-link__count"><?= $counts['custom-tours'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'virtual-experience-signups' ? ' is-active' : '' ?>" href="<?= h(url('/admin/virtual-experience-signups/index.php')) ?>">
          <?= render_nav_glyph('video') ?>Virtual Experience Waitlist <span class="nav-link__count"><?= $counts['virtual-experience-signups'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'agents' ? ' is-active' : '' ?>" href="<?= h(url('/admin/agents/index.php')) ?>">
          <?= render_nav_glyph('users') ?>Agent Applications <span class="nav-link__count"><?= $counts['agents'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'career-applications' ? ' is-active' : '' ?>" href="<?= h(url('/admin/career-applications/index.php')) ?>">
          <?= render_nav_glyph('briefcase') ?>Career Applications <span class="nav-link__count"><?= $counts['career-applications'] ?></span>
        </a>
        <a class="nav-link<?= $active_nav === 'messages' ? ' is-active' : '' ?>" href="<?= h(url('/admin/messages/index.php')) ?>">
          <?= render_nav_glyph('mail') ?>Messages <span class="nav-link__count"><?= $counts['messages'] ?></span>
        </a>
      </div>
      <div class="nav-group">
        <div class="nav-group__label">Settings</div>
        <a class="nav-link<?= $active_nav === 'settings' ? ' is-active' : '' ?>" href="<?= h(url('/admin/settings/index.php')) ?>"><?= render_nav_glyph('sliders') ?>Homepage</a>
        <?php if (($admin['role'] ?? '') === 'super_admin'): ?>
          <a class="nav-link<?= $active_nav === 'users' ? ' is-active' : '' ?>" href="<?= h(url('/admin/users/index.php')) ?>"><?= render_nav_glyph('users') ?>Admin Users</a>
        <?php endif; ?>
      </div>
    </nav>
    <div class="sidebar__footer">
      <span class="sidebar__user"><?= h($admin['name'] ?? '') ?></span>
      <span class="sidebar__role"><?= h(str_replace('_', ' ', $admin['role'] ?? '')) ?></span>
      <a class="sidebar__logout" href="<?= h(url('/admin/logout.php')) ?>">Log out</a>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <div>
        <?php if (!empty($page_eyebrow)): ?><span class="topbar__eyebrow"><?= h($page_eyebrow) ?></span><?php endif; ?>
        <div class="topbar__title"><?= h($page_title ?? '') ?></div>
      </div>
      <?php if (!empty($page_action_html)): ?><?= $page_action_html ?><?php endif; ?>
    </header>
    <div class="content">
      <?php foreach (flash_get() as $flash): ?>
        <div class="flash flash--<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
      <?php endforeach; ?>
