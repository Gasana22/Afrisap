<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$destination = ['experience_type_id' => '', 'name' => '', 'location' => '', 'short_overview' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM experience_destinations WHERE id = ?');
    $stmt->execute([$id]);
    $destination = $stmt->fetch();
    if (!$destination) {
        flash_set('error', 'That destination no longer exists.');
        redirect('/admin/experience-destinations/index.php');
    }
}

$types = db()->query('SELECT id, name FROM experience_types ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $destination['experience_type_id'] = (int) ($_POST['experience_type_id'] ?? 0);
    $destination['name'] = trim($_POST['name'] ?? '');
    $destination['location'] = trim($_POST['location'] ?? '');
    $destination['short_overview'] = trim($_POST['short_overview'] ?? '');

    if ($destination['name'] === '') {
        $errors['name'] = 'Enter a name.';
    }
    if (!$destination['experience_type_id']) {
        $errors['experience_type_id'] = 'Choose an experience type.';
    }

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE experience_destinations SET experience_type_id=?, name=?, location=?, short_overview=? WHERE id=?')
                ->execute([$destination['experience_type_id'], $destination['name'], $destination['location'], $destination['short_overview'], $id]);
        } else {
            db()->prepare('INSERT INTO experience_destinations (experience_type_id, name, location, short_overview) VALUES (?,?,?,?)')
                ->execute([$destination['experience_type_id'], $destination['name'], $destination['location'], $destination['short_overview']]);
            $id = (int) db()->lastInsertId();
        }
        flash_set('success', 'Destination saved.');
        redirect('/admin/experience-destinations/manage.php?id=' . $id);
    }
}

$page_title = $id ? 'Edit experience destination' : 'Add experience destination';
$page_eyebrow = 'Experiential';
$active_nav = 'experience-destinations';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" value="<?= h($destination['name']) ?>" placeholder="e.g. Buganda, Coffee Farm, Football" autofocus required>
          <?php if (isset($errors['name'])): ?><span class="error-text"><?= h($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['experience_type_id']) ? ' has-error' : '' ?>">
          <label for="experience_type_id">Experience type</label>
          <select id="experience_type_id" name="experience_type_id" required>
            <option value="">Select&hellip;</option>
            <?php foreach ($types as $type): ?>
              <option value="<?= (int) $type['id'] ?>" <?= (int) $destination['experience_type_id'] === (int) $type['id'] ? 'selected' : '' ?>><?= h($type['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['experience_type_id'])): ?><span class="error-text"><?= h($errors['experience_type_id']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" value="<?= h($destination['location']) ?>">
        </div>
        <div class="form-field form-field--full">
          <label for="short_overview">Short overview</label>
          <textarea id="short_overview" name="short_overview"><?= h($destination['short_overview']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <span class="hint">Activities and gallery are managed from the destination detail screen once it's saved.</span>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save destination</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/experience-destinations/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
