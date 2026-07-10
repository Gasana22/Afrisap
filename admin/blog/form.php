<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/uploads.php';
require_login();

function slugify_blog(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$post = ['title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'author' => '', 'status' => 'draft', 'cover_image_path' => null];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) {
        flash_set('error', 'That post no longer exists.');
        redirect('/admin/blog/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $post['title'] = trim($_POST['title'] ?? '');
    $post['excerpt'] = trim($_POST['excerpt'] ?? '');
    $post['body'] = trim($_POST['body'] ?? '');
    $post['author'] = trim($_POST['author'] ?? '');
    $post['status'] = $_POST['status'] ?? 'draft';
    $post['slug'] = $_POST['slug'] !== '' ? slugify_blog($_POST['slug']) : slugify_blog($post['title']);

    if ($post['title'] === '') {
        $errors['title'] = 'Enter a title.';
    }
    if ($post['slug'] === '') {
        $errors['slug'] = 'Could not build a slug from that title.';
    }

    $coverPath = null;
    try {
        $coverPath = handle_image_upload('cover_image');
    } catch (RuntimeException $e) {
        $errors['cover_image'] = $e->getMessage();
    }

    if (!$errors) {
        if ($coverPath !== null) {
            $post['cover_image_path'] = $coverPath;
        }
        $publishedAt = $post['status'] === 'published' ? date('Y-m-d H:i:s') : null;
        try {
            if ($id) {
                db()->prepare('UPDATE blog_posts SET title=?, slug=?, excerpt=?, body=?, author=?, status=?, cover_image_path=?, published_at = COALESCE(published_at, ?) WHERE id=?')
                    ->execute([$post['title'], $post['slug'], $post['excerpt'], $post['body'], $post['author'], $post['status'], $post['cover_image_path'], $publishedAt, $id]);
            } else {
                db()->prepare('INSERT INTO blog_posts (title, slug, excerpt, body, author, status, cover_image_path, published_at) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$post['title'], $post['slug'], $post['excerpt'], $post['body'], $post['author'], $post['status'], $post['cover_image_path'], $publishedAt]);
            }
            flash_set('success', 'Post saved.');
            redirect('/admin/blog/index.php');
        } catch (PDOException $e) {
            $errors['slug'] = str_contains($e->getMessage(), 'Duplicate') ? 'That slug is already used.' : 'Could not save the post.';
        }
    }
}

$page_title = $id ? 'Edit post' : 'Add post';
$page_eyebrow = 'About Us';
$active_nav = 'blog';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" value="<?= h($post['title']) ?>" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
          <label for="slug">Slug</label>
          <input type="text" id="slug" name="slug" value="<?= h($post['slug']) ?>">
          <?php if (isset($errors['slug'])): ?><span class="error-text"><?= h($errors['slug']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="author">Author</label>
          <input type="text" id="author" name="author" value="<?= h($post['author']) ?>">
        </div>
        <div class="form-field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
          </select>
        </div>
        <div class="form-field<?= isset($errors['cover_image']) ? ' has-error' : '' ?>">
          <label for="cover_image">Cover image</label>
          <?php if ($post['cover_image_path']): ?>
            <img src="<?= h(url('/' . $post['cover_image_path'])) ?>" alt="" style="width:80px;height:60px;object-fit:cover;border-radius:4px;margin-bottom:6px;">
          <?php endif; ?>
          <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['cover_image'])): ?><span class="error-text"><?= h($errors['cover_image']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="excerpt">Excerpt</label>
          <textarea id="excerpt" name="excerpt"><?= h($post['excerpt']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="body">Body</label>
          <textarea id="body" name="body" style="min-height:280px;"><?= h($post['body']) ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save post</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/blog/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
