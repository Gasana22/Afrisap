<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_login();

$tourId = (int) ($_GET['tour_id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$tStmt = db()->prepare('SELECT * FROM trip_tours WHERE id = ?');
$tStmt->execute([$tourId]);
$tour = $tStmt->fetch();

if (!$tour) {
    flash_set('error', 'That tour no longer exists.');
    redirect('/admin/trip-tours/index.php');
}

$day = ['day_number' => '', 'title' => '', 'description' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM trip_tour_itinerary_days WHERE id = ? AND trip_tour_id = ?');
    $stmt->execute([$id, $tourId]);
    $day = $stmt->fetch();
    if (!$day) {
        flash_set('error', 'That itinerary day no longer exists.');
        redirect('/admin/trip-tours/manage.php?id=' . $tourId);
    }
} else {
    $nextStmt = db()->prepare('SELECT COALESCE(MAX(day_number), 0) + 1 FROM trip_tour_itinerary_days WHERE trip_tour_id = ?');
    $nextStmt->execute([$tourId]);
    $day['day_number'] = (string) $nextStmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $day['day_number'] = trim($_POST['day_number'] ?? '');
    $day['title'] = trim($_POST['title'] ?? '');
    $day['description'] = trim($_POST['description'] ?? '');

    if ($day['day_number'] === '' || (int) $day['day_number'] < 1) {
        $errors['day_number'] = 'Enter a day number of 1 or more.';
    }
    if ($day['title'] === '') {
        $errors['title'] = 'Enter a title for this day.';
    }

    if (!$errors) {
        $dupStmt = db()->prepare('SELECT id FROM trip_tour_itinerary_days WHERE trip_tour_id = ? AND day_number = ? AND id != ?');
        $dupStmt->execute([$tourId, (int) $day['day_number'], $id ?? 0]);
        if ($dupStmt->fetch()) {
            $errors['day_number'] = 'Day ' . (int) $day['day_number'] . ' is already used -- pick a different number.';
        }
    }

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE trip_tour_itinerary_days SET day_number = ?, title = ?, description = ? WHERE id = ?')
                ->execute([(int) $day['day_number'], $day['title'], $day['description'], $id]);
        } else {
            db()->prepare('INSERT INTO trip_tour_itinerary_days (trip_tour_id, day_number, title, description) VALUES (?, ?, ?, ?)')
                ->execute([$tourId, (int) $day['day_number'], $day['title'], $day['description']]);
        }
        flash_set('success', 'Itinerary day saved.');
        redirect('/admin/trip-tours/manage.php?id=' . $tourId);
    }
}

$page_title = ($id ? 'Edit' : 'Add') . ' itinerary day · ' . $tour['title'];
$page_eyebrow = 'Trip';
$active_nav = 'trip-tours';

require __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['day_number']) ? ' has-error' : '' ?>">
          <label for="day_number">Day number</label>
          <input type="number" id="day_number" name="day_number" min="1" value="<?= h((string) $day['day_number']) ?>" autofocus required>
          <?php if (isset($errors['day_number'])): ?><span class="error-text"><?= h($errors['day_number']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" value="<?= h($day['title']) ?>" placeholder="e.g. Boat transfer to the island" required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="description">Description</label>
          <textarea id="description" name="description"><?= h($day['description'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save day</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/trip-tours/manage.php?id=' . $tourId)) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
