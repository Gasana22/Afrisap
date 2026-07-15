<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pagination = paginate_params(9);

$total = (int) db_value("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
$posts = db_all(
    "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT :limit OFFSET :offset",
    ['limit' => $pagination['per_page'], 'offset' => $pagination['offset']]
);
$totalPages = max(1, (int) ceil($total / $pagination['per_page']));

render_header(['title' => 'Blog', 'context' => 'public']);
render_public_navbar();
?>
<section class="hero-section py-5">
  <div class="container text-center">
    <h1 class="fw-bold">From the Field</h1>
    <p class="lead">News, tips and stories from the Smart Farm Platform team and our farming community.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <?php if (!$posts): ?>
      <?php render_empty_state('No blog posts published yet. Check back soon!', 'bi-journal-text'); ?>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($posts as $post): ?>
          <div class="col-md-6 col-lg-4">
            <div class="blog-card content-card h-100 p-0 overflow-hidden">
              <?php if (!empty($post['featured_image'])): ?>
                <img src="<?= e(uploaded_file_url($post['featured_image'])) ?>" alt="<?= e($post['title']) ?>" class="w-100" style="height:180px;object-fit:cover;">
              <?php else: ?>
                <div class="blog-thumb"></div>
              <?php endif; ?>
              <div class="p-4">
                <?php if (!empty($post['category'])): ?>
                  <span class="badge bg-light text-primary border mb-2"><?= e(humanize($post['category'])) ?></span>
                <?php endif; ?>
                <h5 class="fw-semibold"><a class="text-decoration-none text-dark" href="<?= base_url('public/blog-post.php?slug=' . urlencode($post['slug'])) ?>"><?= e($post['title']) ?></a></h5>
                <p class="text-muted small mb-2"><?= e(format_date($post['published_at'])) ?></p>
                <p class="text-muted"><?= e($post['excerpt'] ?? '') ?></p>
                <a href="<?= base_url('public/blog-post.php?slug=' . urlencode($post['slug'])) ?>" class="fw-semibold text-decoration-none">Read more <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
          <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item<?= $p === $pagination['page'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= base_url('public/blog.php?page=' . $p) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
