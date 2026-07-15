<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$slug = clean_string($_GET['slug'] ?? '');
$post = $slug ? db_one("SELECT * FROM blog_posts WHERE slug = :slug AND status = 'published'", ['slug' => $slug]) : null;

render_header(['title' => $post['title'] ?? 'Post Not Found', 'context' => 'public']);
render_public_navbar();
?>
<section class="py-5">
  <div class="container" style="max-width: 820px;">
    <?php if (!$post): ?>
      <?php render_empty_state('Sorry, we could not find that blog post. It may have been unpublished or the link is incorrect.', 'bi-journal-x'); ?>
      <div class="text-center">
        <a href="<?= base_url('public/blog.php') ?>" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Back to Blog</a>
      </div>
    <?php else: ?>
      <a href="<?= base_url('public/blog.php') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to Blog</a>
      <?php if (!empty($post['category'])): ?>
        <span class="badge bg-light text-primary border mt-3 mb-2 d-inline-block"><?= e(humanize($post['category'])) ?></span>
      <?php endif; ?>
      <h1 class="fw-bold mt-2"><?= e($post['title']) ?></h1>
      <p class="text-muted mb-4"><?= e(format_date($post['published_at'])) ?></p>

      <?php if (!empty($post['featured_image'])): ?>
        <img src="<?= e(uploaded_file_url($post['featured_image'])) ?>" alt="<?= e($post['title']) ?>" class="w-100 rounded-4 mb-4" style="max-height:420px;object-fit:cover;">
      <?php else: ?>
        <div class="blog-thumb rounded-4 mb-4" style="height:260px;"></div>
      <?php endif; ?>

      <div class="post-content">
        <?= $post['content'] ?>
      </div>

      <?php
      $tags = json_decode($post['tags'] ?? '[]', true) ?: [];
      if ($tags):
      ?>
        <div class="mt-4 pt-3 border-top">
          <?php foreach ($tags as $tag): ?>
            <span class="badge bg-light text-muted border me-1">#<?= e((string) $tag) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
