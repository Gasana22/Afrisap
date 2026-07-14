<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$page_title = 'Virtual Experience — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Specialised Tours &middot; Coming Soon</p>
    <h1 class="page-header__title">Virtual Experience</h1>
    <p class="page-header__lead">Watch an experience happen live in East Africa from wherever you are -- gorilla trekking, chimp tracking, birding and more, streamed in real time.</p>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:760px;">
    <p class="empty-note">This is a new sector for us and it's still being built. <a href="<?= h(url('/contact.php')) ?>">Get in touch</a> if you'd like to be notified when it launches.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
