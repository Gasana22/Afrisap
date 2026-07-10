<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$type = ($_GET['type'] ?? 'safari') === 'experiential' ? 'experiential' : 'safari';
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

$page_title = ($type === 'safari' ? 'Safari Quote' : 'Experiential Quote') . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">About Us</p>
    <h1 class="page-header__title"><?= $type === 'safari' ? 'Safari Quote' : 'Experiential Quote' ?></h1>
    <p class="page-header__lead">Tell us what you're picturing and we'll put together a tailored quote within 48 hours.</p>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:640px;">
    <?php if ($sent): ?>
      <div class="flash flash--success">Thanks — your quote request has been sent. We'll be in touch shortly.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/quote-submit.php')) ?>">
        <input type="hidden" name="quote_type" value="<?= h($type) ?>">
        <?= csrf_field() ?>
        <div class="site-form__row">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>
        <div class="site-form__row">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="site-form__row">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone">
        </div>
        <div class="site-form__row">
          <label for="details">Tell us what you're planning</label>
          <textarea id="details" name="details" rows="6" placeholder="Dates, group size, interests, budget..." required></textarea>
        </div>
        <button type="submit" class="btn btn--primary">Request Quote</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
