<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/uploads.php';
require_login();

function slugify_experience_type(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$type = ['name' => '', 'slug' => '', 'sort_order' => 0, 'image_path' => null, 'short_description' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM experience_types WHERE id = ?');
    $stmt->execute([$id]);
    $type = $stmt->fetch();
    if (!$type) {
        flash_set('error', 'That experience type no longer exists.');
        redirect('/admin/experience-types/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $type['name'] = trim($_POST['name'] ?? '');
    $type['short_description'] = trim($_POST['short_description'] ?? '');
    $type['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $type['slug'] = $_POST['slug'] !== '' ? slugify_experience_type($_POST['slug']) : slugify_experience_type($type['name']);

    if ($type['name'] === '') {
        $errors['name'] = 'Enter a name.';
    }
    if ($type['slug'] === '') {
        $errors['slug'] = 'Could not build a slug from that name.';
    }

    $imagePath = null;
    try {
        $imagePath = handle_image_upload('image');
    } catch (RuntimeException $e) {
        $errors['image'] = $e->getMessage();
    }

    if (!$errors) {
        if ($imagePath !== null) {
            $type['image_path'] = $imagePath;
        }
        try {
            if ($id) {
                $stmt = db()->prepare('UPDATE experience_types SET name = ?, slug = ?, sort_order = ?, image_path = ?, short_description = ? WHERE id = ?');
                $stmt->execute([$type['name'], $type['slug'], $type['sort_order'], $type['image_path'], $type['short_description'], $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO experience_types (name, slug, sort_order, image_path, short_description) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$type['name'], $type['slug'], $type['sort_order'], $type['image_path'], $type['short_description']]);
            }
            flash_set('success', 'Experience type saved.');
            redirect('/admin/experience-types/index.php');
        } catch (PDOException $e) {
            $errors['slug'] = str_contains($e->getMessage(), 'Duplicate')
                ? 'That name or slug is already used by another experience type.'
                : 'Could not save the experience type.';
        }
    }
}

$page_title = $id ? 'Edit experience type' : 'Add experience type';
$page_eyebrow = 'Experiential';
$active_nav = 'experience-types';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" value="<?= h($type['name']) ?>" placeholder="e.g. Cultural Experience" autofocus required>
          <?php if (isset($errors['name'])): ?><span class="error-text"><?= h($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
          <label for="slug">Slug</label>
          <input type="text" id="slug" name="slug" value="<?= h($type['slug']) ?>" placeholder="auto-generated from name if left blank">
          <span class="hint">Used in the page URL. Leave blank to generate from the name.</span>
          <?php if (isset($errors['slug'])): ?><span class="error-text"><?= h($errors['slug']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['image']) ? ' has-error' : '' ?>">
          <label for="image">Card photo</label>
          <?php if ($type['image_path']): ?>
            <img src="<?= h(url('/' . $type['image_path'])) ?>" alt="" style="width:120px;height:80px;object-fit:cover;border-radius:6px;border:1px solid var(--line);margin-bottom:6px;display:block;">
          <?php endif; ?>
          <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
          <span class="hint">Shown on the homepage "Five ways into everyday Uganda" card. Falls back to a plain icon card if left empty.</span>
          <?php if (isset($errors['image'])): ?><span class="error-text"><?= h($errors['image']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="short_description">Short description</label>
          <input type="text" id="short_description" name="short_description" value="<?= h($type['short_description']) ?>" maxlength="200" placeholder="A few words for the homepage card">
        </div>
        <div class="form-field">
          <label for="sort_order">Menu order</label>
          <input type="number" id="sort_order" name="sort_order" value="<?= (int) $type['sort_order'] ?>" min="0">
          <span class="hint">Controls the order and the number badge shown in the Experiential Tours nav dropdown.</span>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save experience type</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/experience-types/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
