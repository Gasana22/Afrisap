<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';
$prefillSubject = trim($_GET['subject'] ?? '');

$page_title = 'Contact Us — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Get In Touch</p>
    <h1 class="page-header__title">Contact Us</h1>
    <p class="page-header__lead">24/7 Support &middot; <a href="tel:+256393246926" style="color:inherit;text-decoration:underline;">+256 393 246 926</a> &middot; WhatsApp <a href="https://wa.me/256775328952" style="color:inherit;text-decoration:underline;">+256 775 328 952</a></p>
  </div>
</header>

<section class="section">
  <div class="detail-grid wrap">
    <div class="detail-main">
      <?php if ($sent): ?>
        <div class="flash flash--success">Thanks — your message has been sent. We'll reply within 24 hours.</div>
      <?php else: ?>
        <form class="site-form" method="post" action="<?= h(url('/contact-submit.php')) ?>">
          <?= csrf_field() ?>
          <div class="site-form__row">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" required>
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
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?= h($prefillSubject) ?>">
          </div>
          <div class="site-form__row">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="6" required></textarea>
          </div>
          <button type="submit" class="btn btn--primary">Send Message</button>
        </form>
      <?php endif; ?>
    </div>
    <aside class="detail-side">
      <div class="side-card">
        <p class="side-card__title">Branches</p>
        <p class="side-card__body">Kampala &middot; Kigali</p>
      </div>
      <div class="side-card">
        <p class="side-card__title">Email</p>
        <p class="side-card__body"><a href="mailto:info@safarisap.com" style="color:inherit;">info@safarisap.com</a></p>
      </div>
    </aside>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
