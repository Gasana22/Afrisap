<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$destination = ['country_id' => '', 'name' => '', 'overview' => '', 'why_consider' => '', 'additional_info' => '', 'is_featured' => 0];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM destinations WHERE id = ?');
    $stmt->execute([$id]);
    $destination = $stmt->fetch();
    if (!$destination) {
        flash_set('error', 'That destination no longer exists.');
        redirect('/admin/destinations/index.php');
    }
}

$countries = db()->query('SELECT id, name FROM countries ORDER BY name')->fetchAll();

if (!$countries) {
    flash_set('error', 'Add a country before creating destinations.');
    redirect('/admin/countries/form.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $destination['country_id'] = (int) ($_POST['country_id'] ?? 0);
    $destination['name'] = trim($_POST['name'] ?? '');
    $destination['overview'] = trim($_POST['overview'] ?? '');
    $destination['why_consider'] = trim($_POST['why_consider'] ?? '');
    $destination['additional_info'] = trim($_POST['additional_info'] ?? '');
    $destination['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    if ($destination['name'] === '') {
        $errors['name'] = 'Enter a destination name.';
    }
    if (!$destination['country_id']) {
        $errors['country_id'] = 'Choose a country.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = db()->prepare('UPDATE destinations SET country_id = ?, name = ?, overview = ?, why_consider = ?, additional_info = ?, is_featured = ? WHERE id = ?');
            $stmt->execute([$destination['country_id'], $destination['name'], $destination['overview'], $destination['why_consider'], $destination['additional_info'], $destination['is_featured'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO destinations (country_id, name, overview, why_consider, additional_info, is_featured) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$destination['country_id'], $destination['name'], $destination['overview'], $destination['why_consider'], $destination['additional_info'], $destination['is_featured']]);
            $id = (int) db()->lastInsertId();
        }
        flash_set('success', 'Destination saved.');
        redirect('/admin/destinations/manage.php?id=' . $id);
    }
}

$page_title = $id ? 'Edit destination' : 'Add destination';
$page_eyebrow = 'Safari';
$active_nav = 'destinations';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="name">Destination name</label>
          <input type="text" id="name" name="name" value="<?= h($destination['name']) ?>" placeholder="e.g. Bwindi Impenetrable National Park" autofocus required>
          <?php if (isset($errors['name'])): ?><span class="error-text"><?= h($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['country_id']) ? ' has-error' : '' ?>">
          <label for="country_id">Country</label>
          <select id="country_id" name="country_id" required>
            <option value="">Select a country&hellip;</option>
            <?php foreach ($countries as $country): ?>
              <option value="<?= (int) $country['id'] ?>" <?= (int) $destination['country_id'] === (int) $country['id'] ? 'selected' : '' ?>><?= h($country['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['country_id'])): ?><span class="error-text"><?= h($errors['country_id']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="overview">Overview</label>
          <textarea id="overview" name="overview"><?= h($destination['overview']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="why_consider">Why consider this destination</label>
          <textarea id="why_consider" name="why_consider"><?= h($destination['why_consider']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="additional_info">Additional information</label>
          <textarea id="additional_info" name="additional_info"><?= h($destination['additional_info']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label style="font-weight:400;"><input type="checkbox" name="is_featured" value="1" <?= $destination['is_featured'] ? 'checked' : '' ?>> Featured on the homepage</label>
          <span class="hint">Shown in the homepage's "National parks & game reserves" section. Leave unchecked for destinations that should only appear on their own page and in listings.</span>
        </div>
        <div class="form-field form-field--full">
          <span class="hint">Animals found, birds found, popular activities and gallery are managed from the destination detail screen once it's saved.</span>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save destination</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/destinations/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
