<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$careers = db()->query("SELECT * FROM careers WHERE status = 'open' ORDER BY posted_at DESC")->fetchAll();

$page_title = 'Careers — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Work With Us</p>
    <h1 class="page-header__title">Careers</h1>
    <p class="page-header__lead">Join the team planning East Africa's safari and experiential tours.</p>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:760px;">
    <?php if (!$careers): ?>
      <p class="empty-note">No open roles right now. Check back soon, or <a href="<?= h(url('/contact.php')) ?>">send us your CV anyway</a>.</p>
    <?php else: ?>
      <div class="list-rows">
        <?php foreach ($careers as $career): ?>
          <a href="<?= h(url('/career.php?id=' . $career['id'])) ?>" class="list-row">
            <div>
              <div class="list-row__title"><?= h($career['title']) ?></div>
              <div class="list-row__meta"><?= h($career['location'] ?: 'Location flexible') ?></div>
            </div>
            <span class="btn btn--dark" style="padding:8px 14px;font-size:13px;">View &amp; apply</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
