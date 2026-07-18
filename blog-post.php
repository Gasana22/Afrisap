<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found - Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That post isn\'t available. <a href="' . h(url('/blog.php')) . '">Back to the blog</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$page_title = $post['title'] . ' - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Blog &middot; <?= h($post['author'] ?: 'Safarisap') ?></p>
    <h1 class="page-header__title"><?= h($post['title']) ?></h1>
    <p class="page-header__lead"><?= h(date('F j, Y', strtotime((string) $post['published_at']))) ?></p>
  </div>
</header>

<?php if ($post['cover_image_path']): ?>
<div class="wrap" style="margin-top:-1px;">
  <img src="<?= h(url('/' . $post['cover_image_path'])) ?>" alt="" style="width:100%;height:360px;object-fit:cover;border-radius:8px;">
</div>
<?php endif; ?>

<section class="section">
  <div class="wrap" style="max-width:720px;">
    <p class="detail-text" style="font-size:16px;"><?= nl2br(h($post['body'])) ?></p>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
