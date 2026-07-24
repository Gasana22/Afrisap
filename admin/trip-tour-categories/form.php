<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$category = ['name' => '', 'slug' => '', 'sort_order' => 0];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM trip_tour_categories WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    if (!$category) {
        flash_set('error', 'That category no longer exists.');
        redirect('/admin/trip-tour-categories/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $category['name'] = trim($_POST['name'] ?? '');
    $category['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $category['slug'] = $_POST['slug'] !== '' ? slugify($_POST['slug']) : slugify($category['name']);

    if ($category['name'] === '') {
        $errors['name'] = 'Enter a category name.';
    }
    if ($category['slug'] === '') {
        $errors['slug'] = 'Could not build a slug from that name.';
    }

    if (!$errors) {
        try {
            if ($id) {
                $stmt = db()->prepare('UPDATE trip_tour_categories SET name = ?, slug = ?, sort_order = ? WHERE id = ?');
                $stmt->execute([$category['name'], $category['slug'], $category['sort_order'], $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO trip_tour_categories (name, slug, sort_order) VALUES (?, ?, ?)');
                $stmt->execute([$category['name'], $category['slug'], $category['sort_order']]);
            }
            flash_set('success', 'Category saved.');
            redirect('/admin/trip-tour-categories/index.php');
        } catch (PDOException $e) {
            $errors['slug'] = str_contains($e->getMessage(), 'Duplicate')
                ? 'That slug is already used by another category.'
                : 'Could not save the category.';
        }
    }
}

$page_title = $id ? 'Edit trip tour category' : 'Add trip tour category';
$page_eyebrow = 'Lookups';
$active_nav = 'trip-tour-categories';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="name">Category name</label>
          <input type="text" id="name" name="name" value="<?= h($category['name']) ?>" placeholder="e.g. Island Trips" autofocus required>
          <?php if (isset($errors['name'])): ?><span class="error-text"><?= h($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
          <label for="slug">Slug</label>
          <input type="text" id="slug" name="slug" value="<?= h($category['slug']) ?>" placeholder="auto-generated from name if left blank">
          <span class="hint">Used in the page URL. Leave blank to generate from the name.</span>
          <?php if (isset($errors['slug'])): ?><span class="error-text"><?= h($errors['slug']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="sort_order">Menu order</label>
          <input type="number" id="sort_order" name="sort_order" value="<?= (int) $category['sort_order'] ?>" min="0">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save category</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/trip-tour-categories/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
