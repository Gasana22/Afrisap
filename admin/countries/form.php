<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$country = ['name' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM countries WHERE id = ?');
    $stmt->execute([$id]);
    $country = $stmt->fetch();
    if (!$country) {
        flash_set('error', 'That country no longer exists.');
        redirect('/admin/countries/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $country['name'] = trim($_POST['name'] ?? '');

    if ($country['name'] === '') {
        $errors['name'] = 'Enter a country name.';
    }

    if (!$errors) {
        try {
            if ($id) {
                $stmt = db()->prepare('UPDATE countries SET name = ? WHERE id = ?');
                $stmt->execute([$country['name'], $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO countries (name) VALUES (?)');
                $stmt->execute([$country['name']]);
            }
            flash_set('success', 'Country saved.');
            redirect('/admin/countries/index.php');
        } catch (PDOException $e) {
            $errors['name'] = str_contains($e->getMessage(), 'Duplicate')
                ? 'A country with that name already exists.'
                : 'Could not save the country.';
        }
    }
}

$page_title = $id ? 'Edit country' : 'Add country';
$page_eyebrow = 'Lookups';
$active_nav = 'countries';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="name">Country name</label>
          <input type="text" id="name" name="name" value="<?= h($country['name']) ?>" autofocus required>
          <?php if (isset($errors['name'])): ?><span class="error-text"><?= h($errors['name']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save country</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/countries/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
