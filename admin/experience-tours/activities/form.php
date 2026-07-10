<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_login();

$tourId = (int) ($_GET['tour_id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$tStmt = db()->prepare('SELECT * FROM experience_tours WHERE id = ?');
$tStmt->execute([$tourId]);
$tour = $tStmt->fetch();

if (!$tour) {
    flash_set('error', 'That tour no longer exists.');
    redirect('/admin/experience-tours/index.php');
}

$activity = ['title' => '', 'description' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM experience_tour_activities WHERE id = ? AND experience_tour_id = ?');
    $stmt->execute([$id, $tourId]);
    $activity = $stmt->fetch();
    if (!$activity) {
        flash_set('error', 'That activity no longer exists.');
        redirect('/admin/experience-tours/manage.php?id=' . $tourId);
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
            db()->prepare('UPDATE experience_tour_activities SET title = ?, description = ? WHERE id = ?')
                ->execute([$activity['title'], $activity['description'], $id]);
        } else {
            $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM experience_tour_activities WHERE experience_tour_id = ?');
            $stmt->execute([$tourId]);
            $nextOrder = (int) $stmt->fetchColumn();
            db()->prepare('INSERT INTO experience_tour_activities (experience_tour_id, title, description, sort_order) VALUES (?, ?, ?, ?)')
                ->execute([$tourId, $activity['title'], $activity['description'], $nextOrder]);
        }
        flash_set('success', 'Activity saved.');
        redirect('/admin/experience-tours/manage.php?id=' . $tourId);
    }
}

$page_title = ($id ? 'Edit' : 'Add') . ' activity · ' . $tour['title'];
$page_eyebrow = 'Experiential';
$active_nav = 'experience-tours';

require __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" value="<?= h($activity['title']) ?>" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="description">Description</label>
          <textarea id="description" name="description"><?= h($activity['description']) ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/experience-tours/manage.php?id=' . $tourId)) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
