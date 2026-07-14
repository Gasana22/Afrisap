<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$career = ['title' => '', 'description' => '', 'location' => '', 'status' => 'open'];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM careers WHERE id = ?');
    $stmt->execute([$id]);
    $career = $stmt->fetch();
    if (!$career) {
        flash_set('error', 'That listing no longer exists.');
        redirect('/admin/careers/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $career['title'] = trim($_POST['title'] ?? '');
    $career['description'] = trim($_POST['description'] ?? '');
    $career['location'] = trim($_POST['location'] ?? '');
    $career['status'] = $_POST['status'] ?? 'open';

    if ($career['title'] === '') {
        $errors['title'] = 'Enter a job title.';
    }

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE careers SET title=?, description=?, location=?, status=? WHERE id=?')
                ->execute([$career['title'], $career['description'], $career['location'], $career['status'], $id]);
        } else {
            db()->prepare('INSERT INTO careers (title, description, location, status) VALUES (?,?,?,?)')
                ->execute([$career['title'], $career['description'], $career['location'], $career['status']]);
        }
        flash_set('success', 'Career listing saved.');
        redirect('/admin/careers/index.php');
    }
}

$page_title = $id ? 'Edit career' : 'Add career';
$page_eyebrow = 'About Us';
$active_nav = 'careers';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Job title</label>
          <input type="text" id="title" name="title" value="<?= h($career['title']) ?>" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" value="<?= h($career['location']) ?>">
        </div>
        <div class="form-field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="open" <?= $career['status'] === 'open' ? 'selected' : '' ?>>Open</option>
            <option value="closed" <?= $career['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
          </select>
        </div>
        <div class="form-field form-field--full">
          <label for="description">Description</label>
          <textarea id="description" name="description" style="min-height:200px;"><?= h($career['description']) ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save listing</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/careers/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
