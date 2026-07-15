<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$activity = [
    'name' => '', 'phone' => '', 'email' => '', 'duration_hours' => '',
    'short_description' => '', 'full_description' => '', 'operator_id' => '', 'destination_id' => '',
];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM activities WHERE id = ?');
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    if (!$activity) {
        flash_set('error', 'That activity no longer exists.');
        redirect('/admin/activities/index.php');
    }
}

$operators = db()->query('SELECT id, company_name FROM tour_operators ORDER BY company_name')->fetchAll();
$destinations = db()->query('SELECT d.id, d.name, c.name AS country_name FROM destinations d JOIN countries c ON c.id = d.country_id ORDER BY c.name, d.name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $activity['name'] = trim($_POST['name'] ?? '');
    $activity['phone'] = trim($_POST['phone'] ?? '');
    $activity['email'] = trim($_POST['email'] ?? '');
    $activity['duration_hours'] = $_POST['duration_hours'] !== '' ? $_POST['duration_hours'] : null;
    $activity['short_description'] = trim($_POST['short_description'] ?? '');
    $activity['full_description'] = trim($_POST['full_description'] ?? '');
    $activity['operator_id'] = $_POST['operator_id'] !== '' ? (int) $_POST['operator_id'] : null;
    $activity['destination_id'] = $_POST['destination_id'] !== '' ? (int) $_POST['destination_id'] : null;

    if ($activity['name'] === '') {
        $errors['name'] = 'Enter an activity name.';
    }
    if ($activity['duration_hours'] !== null && !is_numeric($activity['duration_hours'])) {
        $errors['duration_hours'] = 'Enter a number of hours.';
    }

    if (!$errors) {
        $params = [
            $activity['name'], $activity['phone'], $activity['email'], $activity['duration_hours'],
            $activity['short_description'], $activity['full_description'], $activity['operator_id'], $activity['destination_id'],
        ];
        if ($id) {
            db()->prepare('UPDATE activities SET name=?, phone=?, email=?, duration_hours=?, short_description=?, full_description=?, operator_id=?, destination_id=? WHERE id=?')
                ->execute([...$params, $id]);
        } else {
            db()->prepare('INSERT INTO activities (name, phone, email, duration_hours, short_description, full_description, operator_id, destination_id) VALUES (?,?,?,?,?,?,?,?)')
                ->execute($params);
            $id = (int) db()->lastInsertId();
        }
        flash_set('success', 'Activity saved.');
        redirect('/admin/activities/manage.php?id=' . $id);
    }
}

$page_title = $id ? 'Edit activity' : 'Add activity';
$page_eyebrow = 'Activities';
$active_nav = 'activities';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="name">Activity name</label>
          <input type="text" id="name" name="name" value="<?= h($activity['name']) ?>" autofocus required>
          <?php if (isset($errors['name'])): ?><span class="error-text"><?= h($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['duration_hours']) ? ' has-error' : '' ?>">
          <label for="duration_hours">Duration (hours)</label>
          <input type="number" step="0.5" min="0" id="duration_hours" name="duration_hours" value="<?= h((string) ($activity['duration_hours'] ?? '')) ?>">
          <?php if (isset($errors['duration_hours'])): ?><span class="error-text"><?= h($errors['duration_hours']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone" value="<?= h($activity['phone']) ?>">
        </div>
        <div class="form-field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= h($activity['email']) ?>">
        </div>
        <div class="form-field">
          <label for="operator_id">Company</label>
          <select id="operator_id" name="operator_id">
            <option value="">None</option>
            <?php foreach ($operators as $operator): ?>
              <option value="<?= (int) $operator['id'] ?>" <?= (int) $activity['operator_id'] === (int) $operator['id'] ? 'selected' : '' ?>><?= h($operator['company_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="destination_id">Destination</label>
          <select id="destination_id" name="destination_id">
            <option value="">None</option>
            <?php foreach ($destinations as $destination): ?>
              <option value="<?= (int) $destination['id'] ?>" <?= (int) $activity['destination_id'] === (int) $destination['id'] ? 'selected' : '' ?>><?= h($destination['name']) ?> (<?= h($destination['country_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field form-field--full">
          <label for="short_description">Short description</label>
          <textarea id="short_description" name="short_description"><?= h($activity['short_description']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="full_description">Full description</label>
          <textarea id="full_description" name="full_description"><?= h($activity['full_description']) ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save activity</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/activities/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
