<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/uploads.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$operator = ['company_name' => '', 'phone' => '', 'email' => '', 'logo_path' => null];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM tour_operators WHERE id = ?');
    $stmt->execute([$id]);
    $operator = $stmt->fetch();
    if (!$operator) {
        flash_set('error', 'That operator no longer exists.');
        redirect('/admin/operators/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $operator['company_name'] = trim($_POST['company_name'] ?? '');
    $operator['phone'] = trim($_POST['phone'] ?? '');
    $operator['email'] = trim($_POST['email'] ?? '');

    if ($operator['company_name'] === '') {
        $errors['company_name'] = 'Enter a company name.';
    }

    $logoPath = null;
    try {
        $logoPath = handle_image_upload('logo');
    } catch (RuntimeException $e) {
        $errors['logo'] = $e->getMessage();
    }

    if (!$errors) {
        if ($logoPath !== null) {
            $operator['logo_path'] = $logoPath;
        }
        if ($id) {
            $stmt = db()->prepare('UPDATE tour_operators SET company_name = ?, logo_path = ?, phone = ?, email = ? WHERE id = ?');
            $stmt->execute([$operator['company_name'], $operator['logo_path'], $operator['phone'], $operator['email'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO tour_operators (company_name, logo_path, phone, email) VALUES (?, ?, ?, ?)');
            $stmt->execute([$operator['company_name'], $operator['logo_path'], $operator['phone'], $operator['email']]);
        }
        flash_set('success', 'Operator saved.');
        redirect('/admin/operators/index.php');
    }
}

$page_title = $id ? 'Edit operator' : 'Add operator';
$page_eyebrow = 'Lookups';
$active_nav = 'operators';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['company_name']) ? ' has-error' : '' ?>">
          <label for="company_name">Company name</label>
          <input type="text" id="company_name" name="company_name" value="<?= h($operator['company_name']) ?>" autofocus required>
          <?php if (isset($errors['company_name'])): ?><span class="error-text"><?= h($errors['company_name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['logo']) ? ' has-error' : '' ?>">
          <label for="logo">Logo</label>
          <?php if ($operator['logo_path']): ?>
            <img src="<?= h(url('/' . $operator['logo_path'])) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:4px;border:1px solid var(--line);margin-bottom:6px;">
          <?php endif; ?>
          <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['logo'])): ?><span class="error-text"><?= h($errors['logo']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone" value="<?= h($operator['phone']) ?>">
        </div>
        <div class="form-field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= h($operator['email']) ?>">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save operator</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/operators/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
