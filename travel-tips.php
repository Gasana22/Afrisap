<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$stmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$stmt->execute(['uganda-travel-tips']);
$page = $stmt->fetch();

$page_title = ($page['title'] ?? 'Uganda Travel Tips') . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Travel Tips</p>
    <h1 class="page-header__title"><?= h($page['title'] ?? 'Uganda Travel Tips') ?></h1>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:760px;">
    <?php if ($page && $page['body']): ?>
      <p class="detail-text" style="font-size:16px;"><?= nl2br(h($page['body'])) ?></p>
    <?php else: ?>
      <p class="empty-note">Travel tips are being written — check back soon, or <a href="<?= h(url('/contact.php')) ?>">ask us</a> directly.</p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
