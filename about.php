<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$stmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$stmt->execute(['about']);
$page = $stmt->fetch();

$stats = [
    ['value' => (int) db()->query('SELECT COUNT(*) FROM countries') ->fetchColumn(), 'label' => 'Countries Covered'],
    ['value' => (int) db()->query('SELECT COUNT(*) FROM destinations')->fetchColumn(), 'label' => 'Parks & Destinations'],
    ['value' => (int) db()->query('SELECT COUNT(*) FROM experience_types')->fetchColumn(), 'label' => 'Experience Types'],
    ['value' => 4, 'label' => 'Branch Offices'],
];

$page_title = ($page['title'] ?? 'About Us') . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">About Us</p>
    <h1 class="page-header__title"><?= h($page['title'] ?? 'About Safarisap') ?></h1>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:760px;">
    <div class="stat-band" style="margin-bottom:44px;">
      <?php foreach ($stats as $stat): ?>
        <div class="stat-band__item">
          <div class="stat-band__value"><?= $stat['value'] ?></div>
          <div class="stat-band__label"><?= h($stat['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($page && $page['body']): ?>
      <p class="detail-text" style="font-size:16px;"><?= nl2br(h($page['body'])) ?></p>
    <?php else: ?>
      <p class="empty-note">Our story is being written — check back soon, or <a href="<?= h(url('/contact.php')) ?>">reach out</a> and we'll tell you in person.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap">
    <div class="why-grid">
      <div class="why-item">
        <h3 class="why-item__title">Named operators</h3>
        <p class="why-item__body">Every tour is run by a licensed, named operator whose contact details are shown before you book -- never an anonymous middleman.</p>
      </div>
      <div class="why-item">
        <h3 class="why-item__title">Two kinds of travel</h3>
        <p class="why-item__body">Safaris into the parks, and experiential tours into daily life -- run as genuinely separate, purpose-built products.</p>
      </div>
      <div class="why-item">
        <h3 class="why-item__title">On the ground</h3>
        <p class="why-item__body">Branches in Kampala, Nairobi, Addis Ababa and London mean support in your timezone and staff who know the region.</p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
