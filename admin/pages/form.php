<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

function slugify_page(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$page = ['slug' => '', 'title' => '', 'body' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    $page = $stmt->fetch();
    if (!$page) {
        flash_set('error', 'That page no longer exists.');
        redirect('/admin/pages/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $page['title'] = trim($_POST['title'] ?? '');
    $page['body'] = trim($_POST['body'] ?? '');
    $page['slug'] = $_POST['slug'] !== '' ? slugify_page($_POST['slug']) : slugify_page($page['title']);

    if ($page['title'] === '') {
        $errors['title'] = 'Enter a title.';
    }
    if ($page['slug'] === '') {
        $errors['slug'] = 'Could not build a slug from that title.';
    }

    if (!$errors) {
        try {
            if ($id) {
                db()->prepare('UPDATE pages SET slug = ?, title = ?, body = ? WHERE id = ?')
                    ->execute([$page['slug'], $page['title'], $page['body'], $id]);
            } else {
                db()->prepare('INSERT INTO pages (slug, title, body) VALUES (?, ?, ?)')
                    ->execute([$page['slug'], $page['title'], $page['body']]);
            }
            flash_set('success', 'Page saved.');
            redirect('/admin/pages/index.php');
        } catch (PDOException $e) {
            $errors['slug'] = str_contains($e->getMessage(), 'Duplicate') ? 'That slug is already used.' : 'Could not save the page.';
        }
    }
}

$page_title = $id ? 'Edit page' : 'Add page';
$page_eyebrow = 'About Us';
$active_nav = 'pages';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" value="<?= h($page['title']) ?>" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
          <label for="slug">Slug</label>
          <input type="text" id="slug" name="slug" value="<?= h($page['slug']) ?>">
          <?php if (isset($errors['slug'])): ?><span class="error-text"><?= h($errors['slug']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="body">Body</label>
          <textarea id="body" name="body" style="min-height:260px;"><?= h($page['body']) ?></textarea>
          <span class="hint">Plain text. Blank lines become paragraph breaks on the public page.</span>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save page</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/pages/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
