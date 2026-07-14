<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_login();

$destinationId = (int) ($_GET['destination_id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$dStmt = db()->prepare('SELECT * FROM experience_destinations WHERE id = ?');
$dStmt->execute([$destinationId]);
$destination = $dStmt->fetch();

if (!$destination) {
    flash_set('error', 'That destination no longer exists.');
    redirect('/admin/experience-destinations/index.php');
}

$activity = ['title' => '', 'description' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM experience_destination_activities WHERE id = ? AND experience_destination_id = ?');
    $stmt->execute([$id, $destinationId]);
    $activity = $stmt->fetch();
    if (!$activity) {
        flash_set('error', 'That activity no longer exists.');
        redirect('/admin/experience-destinations/manage.php?id=' . $destinationId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $activity['title'] = trim($_POST['title'] ?? '');
    $activity['description'] = trim($_POST['description'] ?? '');

    if ($activity['title'] === '') {
        $errors['title'] = 'Enter a title.';
    }

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE experience_destination_activities SET title = ?, description = ? WHERE id = ?')
                ->execute([$activity['title'], $activity['description'], $id]);
        } else {
            $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM experience_destination_activities WHERE experience_destination_id = ?');
            $stmt->execute([$destinationId]);
            $nextOrder = (int) $stmt->fetchColumn();
            db()->prepare('INSERT INTO experience_destination_activities (experience_destination_id, title, description, sort_order) VALUES (?, ?, ?, ?)')
                ->execute([$destinationId, $activity['title'], $activity['description'], $nextOrder]);
        }
        flash_set('success', 'Activity saved.');
        redirect('/admin/experience-destinations/manage.php?id=' . $destinationId);
    }
}

$page_title = ($id ? 'Edit' : 'Add') . ' activity · ' . $destination['name'];
$page_eyebrow = 'Experiential';
$active_nav = 'experience-destinations';

require __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" value="<?= h($activity['title']) ?>" placeholder="e.g. Traditional Drumming Session" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="description">Description</label>
          <textarea id="description" name="description"><?= h($activity['description']) ?></textarea>
        </div>
        <?php if ($id): ?>
        <div class="form-field form-field--full">
          <span class="hint">Save first, then use the Gallery button on the destination page to add images.</span>
        </div>
        <?php endif; ?>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/experience-destinations/manage.php?id=' . $destinationId)) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
