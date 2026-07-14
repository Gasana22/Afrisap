<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$page_title = 'Create Your Own Tour — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Specialised Tours</p>
    <h1 class="page-header__title">Create Your Own Tour</h1>
    <p class="page-header__lead">Tell us the trip you're picturing -- destinations, budget, activities, a touch of experiential culture, how many days, how many people -- and a consultant will build it with you.</p>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:760px;">
    <p class="empty-note">The full custom-tour builder is on its way. In the meantime, <a href="<?= h(url('/quote.php?type=safari')) ?>">send us a quote request</a> with what you have in mind and a consultant will respond directly.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
