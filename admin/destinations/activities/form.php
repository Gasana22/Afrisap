<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_login();

$destinationId = (int) ($_GET['destination_id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$dStmt = db()->prepare('SELECT * FROM destinations WHERE id = ?');
$dStmt->execute([$destinationId]);
$destination = $dStmt->fetch();

if (!$destination) {
    flash_set('error', 'That destination no longer exists.');
    redirect('/admin/destinations/index.php');
}

$activity = ['title' => '', 'description' => '', 'activity_id' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM destination_activities WHERE id = ? AND destination_id = ?');
    $stmt->execute([$id, $destinationId]);
    $activity = $stmt->fetch();
    if (!$activity) {
        flash_set('error', 'That activity no longer exists.');
        redirect('/admin/destinations/manage.php?id=' . $destinationId);
    }
}

$catalog = db()->query('SELECT id, name, short_description FROM activities ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $activity['title'] = trim($_POST['title'] ?? '');
    $activity['description'] = trim($_POST['description'] ?? '');
    $activity['activity_id'] = $_POST['activity_id'] !== '' ? (int) $_POST['activity_id'] : null;

    if ($activity['title'] === '') {
        $errors['title'] = 'Enter an activity title.';
    }

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE destination_activities SET title = ?, description = ?, activity_id = ? WHERE id = ?')
                ->execute([$activity['title'], $activity['description'], $activity['activity_id'], $id]);
        } else {
            $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM destination_activities WHERE destination_id = ?');
            $stmt->execute([$destinationId]);
            $nextOrder = (int) $stmt->fetchColumn();
            db()->prepare('INSERT INTO destination_activities (destination_id, title, description, activity_id, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute([$destinationId, $activity['title'], $activity['description'], $activity['activity_id'], $nextOrder]);
        }
        flash_set('success', 'Activity saved.');
        redirect('/admin/destinations/manage.php?id=' . $destinationId);
    }
}

$page_title = ($id ? 'Edit' : 'Add') . ' activity · ' . $destination['name'];
$page_eyebrow = 'Safari';
$active_nav = 'destinations';

require __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full">
          <label for="activity_id">Link to catalog activity (optional)</label>
          <select id="activity_id" name="activity_id">
            <option value="">Not linked &mdash; freeform entry</option>
            <?php foreach ($catalog as $catalogActivity): ?>
              <option value="<?= (int) $catalogActivity['id'] ?>"
                data-name="<?= h($catalogActivity['name']) ?>"
                data-description="<?= h($catalogActivity['short_description'] ?? '') ?>"
                <?= (int) ($activity['activity_id'] ?? 0) === (int) $catalogActivity['id'] ? 'selected' : '' ?>><?= h($catalogActivity['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="hint">Picking one fills the title/description below &mdash; still editable, and this destination keeps its own copy.</span>
        </div>
        <div class="form-field form-field--full<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Activity title</label>
          <input type="text" id="title" name="title" value="<?= h($activity['title']) ?>" placeholder="e.g. Guided Gorilla Trek" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="description">Description</label>
          <textarea id="description" name="description"><?= h($activity['description']) ?></textarea>
        </div>
        <?php if ($id): ?>
        <div class="form-field form-field--full">
          <span class="hint">Save first, then use the Gallery button on the destination page to add images for this activity.</span>
        </div>
        <?php endif; ?>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save activity</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/destinations/manage.php?id=' . $destinationId)) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('activity_id').addEventListener('change', function () {
  var opt = this.options[this.selectedIndex];
  var titleField = document.getElementById('title');
  var descField = document.getElementById('description');
  if (opt.value && !titleField.value) {
    titleField.value = opt.dataset.name || '';
  }
  if (opt.value && !descField.value) {
    descField.value = opt.dataset.description || '';
  }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
