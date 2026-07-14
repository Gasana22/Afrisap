<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Blog Posts';
$page_eyebrow = 'About Us';
$active_nav = 'blog';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/blog/form.php')) . '">Add post</a>';

$posts = db()->query('SELECT * FROM blog_posts ORDER BY created_at DESC')->fetchAll();

$blogStats = ['published' => 0, 'draft' => 0];
foreach ($posts as $post) {
    $blogStats[$post['status'] === 'published' ? 'published' : 'draft']++;
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($posts): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Blog posts</div><div class="stat-tile__value"><?= count($posts) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Published</div><div class="stat-tile__value"><?= $blogStats['published'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Draft</div><div class="stat-tile__value"><?= $blogStats['draft'] ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$posts): ?>
    <div class="empty-state">
      <div class="empty-state__title">No blog posts yet</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/blog/form.php')) ?>">Write the first post</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Title</th><th>Author</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
          <tr>
            <td>
              <div class="table-item">
                <?php if ($post['cover_image_path']): ?>
                  <img class="table-item__thumb" src="<?= h(url('/' . $post['cover_image_path'])) ?>" alt="">
                <?php endif; ?>
                <span><?= h($post['title']) ?></span>
              </div>
            </td>
            <td class="table__meta"><?= h($post['author']) ?></td>
            <td><span class="status-pill status-pill--<?= h($post['status']) ?>"><?= h(ucfirst($post['status'])) ?></span></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/blog/form.php?id=' . $post['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/blog/delete.php')) ?>" onsubmit="return confirm('Delete this post?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
