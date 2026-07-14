<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

$page_title = 'Join the Agent Pool — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Agent Pool</p>
    <h1 class="page-header__title">Join the Agent Pool</h1>
    <p class="page-header__lead">Sell Safarisap tours to your clients and earn commission on every booking. Tell us about your agency.</p>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:640px;">
    <?php if ($sent): ?>
      <div class="flash flash--success">Thanks — we've received your application and will be in touch.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/agent-submit.php')) ?>">
        <?= csrf_field() ?>
        <div class="site-form__row">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>
        <div class="site-form__row">
          <label for="company_name">Company name (if any)</label>
          <input type="text" id="company_name" name="company_name">
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
          <label for="region">Region</label>
          <input type="text" id="region" name="region" placeholder="e.g. Nairobi, Kenya">
        </div>
        <div class="site-form__row">
          <label for="message">Tell us about your agency</label>
          <textarea id="message" name="message" rows="5"></textarea>
        </div>
        <button type="submit" class="btn btn--primary">Submit Application</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
