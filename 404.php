<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

http_response_code(404);

$page_title = 'Page not found — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<section class="section" style="min-height:52vh;display:flex;align-items:center;">
  <div class="wrap" style="text-align:center;max-width:560px;">
    <p class="section__eyebrow">404</p>
    <h1 class="section__title">We couldn't find that page</h1>
    <p class="section__lead" style="margin-bottom:28px;">The page may have moved, or the link may be out of date. Here are a few good places to start instead.</p>
    <div class="hero__actions" style="justify-content:center;">
      <a href="<?= h(url('/index.php')) ?>" class="btn btn--dark">Back to Home</a>
      <a href="<?= h(url('/tours.php')) ?>" class="btn btn--dark">Browse Safaris</a>
      <a href="<?= h(url('/contact.php')) ?>" class="btn btn--dark">Contact Us</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
