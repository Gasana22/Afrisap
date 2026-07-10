<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$posts = db()->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC")->fetchAll();

$page_title = 'Blog — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">About Us</p>
    <h1 class="page-header__title">Blog</h1>
    <p class="page-header__lead">Notes from the road — trip reports, park guides and travel planning tips.</p>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <?php if (!$posts): ?>
      <p class="empty-note">Posts are on the way — check back soon.</p>
    <?php else: ?>
      <div class="card-grid">
        <?php foreach ($posts as $post): ?>
          <a href="<?= h(url('/blog-post.php?slug=' . $post['slug'])) ?>" class="tour-card">
            <div class="tour-card__media">
              <?php if ($post['cover_image_path']): ?>
                <img src="<?= h(url('/' . $post['cover_image_path'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
              <?php endif; ?>
            </div>
            <div class="tour-card__body">
              <p class="tour-card__meta"><?= h($post['author'] ?: 'Safarisap') ?> &middot; <?= h(date('M j, Y', strtotime((string) $post['published_at']))) ?></p>
              <h3 class="tour-card__title"><?= h($post['title']) ?></h3>
              <p class="tour-card__overview"><?= h(mb_strimwidth((string) $post['excerpt'], 0, 120, '…')) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
