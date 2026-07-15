<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

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
  <div class="wrap" style="max-width:640px;">
    <p class="detail-text" style="font-size:16px;">This is a new sector for us, and it's still being built -- real-time streaming from the field takes its own infrastructure, so we're taking the time to get it right rather than rush it out. Leave your email and we'll let you know the moment it's live.</p>

    <?php if ($sent): ?>
      <div class="flash flash--success" style="margin-top:20px;">Thanks — we'll email you the moment Virtual Experience launches.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/virtual-experience-signup.php')) ?>" style="margin-top:24px;">
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
          <label for="notes">Anything you'd want to watch first? (optional)</label>
          <textarea id="notes" name="notes" rows="3" placeholder="e.g. gorilla trekking in Bwindi, a chimp trek in Kibale..."></textarea>
        </div>
        <button type="submit" class="btn btn--primary">Notify Me When It Launches</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
