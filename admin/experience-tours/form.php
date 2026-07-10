<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$tour = [
    'title' => '', 'experience_type_id' => '', 'price' => '', 'discount_percent' => 0, 'days' => '',
    'min_pax' => 1, 'max_pax' => '', 'short_overview' => '', 'full_overview' => '', 'top_highlights' => '', 'status' => 'draft',
];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM experience_tours WHERE id = ?');
    $stmt->execute([$id]);
    $tour = $stmt->fetch();
    if (!$tour) {
        flash_set('error', 'That tour no longer exists.');
        redirect('/admin/experience-tours/index.php');
    }
}

$types = db()->query('SELECT id, name FROM experience_types ORDER BY name')->fetchAll();

if (!$types) {
    flash_set('error', 'Experience types are missing from the database.');
    redirect('/admin/experience-tours/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $tour['title'] = trim($_POST['title'] ?? '');
    $tour['experience_type_id'] = (int) ($_POST['experience_type_id'] ?? 0);
    $tour['price'] = $_POST['price'] ?? '';
    $tour['discount_percent'] = $_POST['discount_percent'] ?? 0;
    $tour['days'] = $_POST['days'] ?? '';
    $tour['min_pax'] = $_POST['min_pax'] ?? 1;
    $tour['max_pax'] = $_POST['max_pax'] ?? '';
    $tour['short_overview'] = trim($_POST['short_overview'] ?? '');
    $tour['full_overview'] = trim($_POST['full_overview'] ?? '');
    $tour['top_highlights'] = trim($_POST['top_highlights'] ?? '');
    $tour['status'] = $_POST['status'] ?? 'draft';

    if ($tour['title'] === '') {
        $errors['title'] = 'Enter a tour title.';
    }
    if (!$tour['experience_type_id']) {
        $errors['experience_type_id'] = 'Choose an experience type.';
    }
    if ($tour['price'] === '' || !is_numeric($tour['price']) || (float) $tour['price'] < 0) {
        $errors['price'] = 'Enter a valid price.';
    }
    if ($tour['days'] === '' || !ctype_digit((string) $tour['days']) || (int) $tour['days'] < 1) {
        $errors['days'] = 'Enter the number of days.';
    }
    if ($tour['max_pax'] === '' || !ctype_digit((string) $tour['max_pax']) || (int) $tour['max_pax'] < 1) {
        $errors['max_pax'] = 'Enter the maximum group size.';
    }
    if (!ctype_digit((string) $tour['min_pax']) || (int) $tour['min_pax'] < 1) {
        $errors['min_pax'] = 'Enter the minimum group size.';
    }
    if (!$errors && (int) $tour['min_pax'] > (int) $tour['max_pax']) {
        $errors['min_pax'] = 'Minimum can\'t be greater than maximum.';
    }
    // Changing the experience type after destinations were picked would orphan the selection
    // (destinations are type-scoped), so once linked, lock the type.
    if ($id && !$errors) {
        $currentType = db()->prepare('SELECT experience_type_id FROM experience_tours WHERE id = ?');
        $currentType->execute([$id]);
        $currentTypeId = (int) $currentType->fetchColumn();
        if ($currentTypeId !== $tour['experience_type_id']) {
            $hasDestinations = db()->prepare('SELECT COUNT(*) FROM experience_tour_destinations WHERE experience_tour_id = ?');
            $hasDestinations->execute([$id]);
            if ((int) $hasDestinations->fetchColumn() > 0) {
                $errors['experience_type_id'] = 'Can\'t change type once destinations are attached. Remove them first.';
            }
        }
    }

    if (!$errors) {
        $params = [
            $tour['title'], $tour['experience_type_id'], $tour['price'], $tour['discount_percent'],
            $tour['days'], $tour['min_pax'], $tour['max_pax'], $tour['short_overview'],
            $tour['full_overview'], $tour['top_highlights'], $tour['status'],
        ];
        if ($id) {
            db()->prepare('UPDATE experience_tours SET title=?, experience_type_id=?, price=?, discount_percent=?, days=?, min_pax=?, max_pax=?, short_overview=?, full_overview=?, top_highlights=?, status=? WHERE id=?')
                ->execute([...$params, $id]);
        } else {
            db()->prepare('INSERT INTO experience_tours (title, experience_type_id, price, discount_percent, days, min_pax, max_pax, short_overview, full_overview, top_highlights, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute($params);
            $id = (int) db()->lastInsertId();
        }
        flash_set('success', 'Tour saved. Now add its destinations, activities and gallery below.');
        redirect('/admin/experience-tours/manage.php?id=' . $id);
    }
}

$page_title = $id ? 'Edit experience tour' : 'Add experience tour';
$page_eyebrow = 'Experiential';
$active_nav = 'experience-tours';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Tour title</label>
          <input type="text" id="title" name="title" value="<?= h($tour['title']) ?>" placeholder="e.g. 3-Day Buganda Cultural Immersion" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['experience_type_id']) ? ' has-error' : '' ?>">
          <label for="experience_type_id">Experience type</label>
          <select id="experience_type_id" name="experience_type_id" required>
            <option value="">Select&hellip;</option>
            <?php foreach ($types as $type): ?>
              <option value="<?= (int) $type['id'] ?>" <?= (int) $tour['experience_type_id'] === (int) $type['id'] ? 'selected' : '' ?>><?= h($type['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['experience_type_id'])): ?><span class="error-text"><?= h($errors['experience_type_id']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="draft" <?= $tour['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= $tour['status'] === 'published' ? 'selected' : '' ?>>Published</option>
          </select>
        </div>
        <div class="form-field<?= isset($errors['price']) ? ' has-error' : '' ?>">
          <label for="price">Price (USD)</label>
          <input type="number" step="0.01" min="0" id="price" name="price" value="<?= h((string) $tour['price']) ?>" required>
          <?php if (isset($errors['price'])): ?><span class="error-text"><?= h($errors['price']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="discount_percent">Discount %</label>
          <input type="number" step="0.01" min="0" max="100" id="discount_percent" name="discount_percent" value="<?= h((string) $tour['discount_percent']) ?>">
        </div>
        <div class="form-field<?= isset($errors['days']) ? ' has-error' : '' ?>">
          <label for="days">Number of days</label>
          <input type="number" min="1" id="days" name="days" value="<?= h((string) $tour['days']) ?>" required>
          <?php if (isset($errors['days'])): ?><span class="error-text"><?= h($errors['days']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['min_pax']) ? ' has-error' : '' ?>">
          <label for="min_pax">Minimum group size</label>
          <input type="number" min="1" id="min_pax" name="min_pax" value="<?= h((string) $tour['min_pax']) ?>" required>
          <?php if (isset($errors['min_pax'])): ?><span class="error-text"><?= h($errors['min_pax']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['max_pax']) ? ' has-error' : '' ?>">
          <label for="max_pax">Maximum group size</label>
          <input type="number" min="1" id="max_pax" name="max_pax" value="<?= h((string) $tour['max_pax']) ?>" required>
          <?php if (isset($errors['max_pax'])): ?><span class="error-text"><?= h($errors['max_pax']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label for="short_overview">Short overview</label>
          <textarea id="short_overview" name="short_overview"><?= h($tour['short_overview']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="full_overview">Full overview</label>
          <span class="hint">Gallery managed after saving.</span>
          <textarea id="full_overview" name="full_overview"><?= h($tour['full_overview']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="top_highlights">Top highlights</label>
          <textarea id="top_highlights" name="top_highlights"><?= h($tour['top_highlights']) ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save tour</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/experience-tours/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
