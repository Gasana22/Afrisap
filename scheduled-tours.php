<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

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
  <div class="wrap" style="max-width:760px;">
    <p class="empty-note">We're setting up scheduled departures now -- check back soon, or <a href="<?= h(url('/contact.php')) ?>">ask us</a> about upcoming dates.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
