<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$tour = [
    'title' => '', 'category_id' => '', 'budget_type' => 'Mid-Range', 'price' => '', 'discount_percent' => 0,
    'days' => '', 'scheduled_date' => '', 'min_pax' => 1, 'max_pax' => '', 'short_overview' => '', 'full_overview' => '',
    'top_highlights' => '', 'hotel_info' => '', 'vehicle_info' => '', 'flight_info' => '',
    'includes' => '', 'excludes' => '', 'operator_id' => '', 'status' => 'draft', 'is_featured' => 0,
];
$errors = [];

$extraCategoryIds = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM tours WHERE id = ?');
    $stmt->execute([$id]);
    $tour = $stmt->fetch();
    if (!$tour) {
        flash_set('error', 'That tour no longer exists.');
        redirect('/admin/tours/index.php');
    }
    $extraStmt = db()->prepare('SELECT category_id FROM tour_extra_categories WHERE tour_id = ?');
    $extraStmt->execute([$id]);
    $extraCategoryIds = array_map('intval', $extraStmt->fetchAll(PDO::FETCH_COLUMN));
}

$categories = db()->query("SELECT * FROM tour_categories ORDER BY FIELD(menu_group,'safari','trip','school'), sort_order, name")->fetchAll();
$operators = db()->query('SELECT id, company_name FROM tour_operators ORDER BY company_name')->fetchAll();

if (!$categories) {
    flash_set('error', 'Add a tour category before creating tours.');
    redirect('/admin/categories/form.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $tour['title'] = trim($_POST['title'] ?? '');
    $tour['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $extraCategoryIds = array_values(array_unique(array_filter(array_map('intval', $_POST['extra_category_ids'] ?? []))));
    $tour['budget_type'] = $_POST['budget_type'] ?? 'Mid-Range';
    $tour['price'] = $_POST['price'] ?? '';
    $tour['discount_percent'] = $_POST['discount_percent'] ?? 0;
    $tour['days'] = $_POST['days'] ?? '';
    $tour['scheduled_date'] = trim($_POST['scheduled_date'] ?? '') ?: null;
    $tour['min_pax'] = $_POST['min_pax'] ?? 1;
    $tour['max_pax'] = $_POST['max_pax'] ?? '';
    $tour['short_overview'] = trim($_POST['short_overview'] ?? '');
    $tour['full_overview'] = trim($_POST['full_overview'] ?? '');
    $tour['top_highlights'] = trim($_POST['top_highlights'] ?? '');
    $tour['hotel_info'] = trim($_POST['hotel_info'] ?? '');
    $tour['vehicle_info'] = trim($_POST['vehicle_info'] ?? '');
    $tour['flight_info'] = trim($_POST['flight_info'] ?? '');
    $tour['includes'] = trim($_POST['includes'] ?? '');
    $tour['excludes'] = trim($_POST['excludes'] ?? '');
    $tour['operator_id'] = $_POST['operator_id'] !== '' ? (int) $_POST['operator_id'] : null;
    $tour['status'] = $_POST['status'] ?? 'draft';
    $tour['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    if ($tour['title'] === '') {
        $errors['title'] = 'Enter a tour title.';
    }
    if (!$tour['category_id']) {
        $errors['category_id'] = 'Choose a category.';
    }
    if ($tour['price'] === '' || !is_numeric($tour['price']) || (float) $tour['price'] < 0) {
        $errors['price'] = 'Enter a valid price.';
    }
    if ($tour['days'] === '' || !ctype_digit((string) $tour['days']) || (int) $tour['days'] < 1) {
        $errors['days'] = 'Enter the number of days.';
    }
    if ($tour['scheduled_date'] !== null && !DateTime::createFromFormat('Y-m-d', $tour['scheduled_date'])) {
        $errors['scheduled_date'] = 'Enter a valid date.';
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

    if (!$errors) {
        $params = [
            $tour['title'], $tour['category_id'], $tour['budget_type'], $tour['price'], $tour['discount_percent'],
            $tour['days'], $tour['scheduled_date'], $tour['min_pax'], $tour['max_pax'], $tour['short_overview'], $tour['full_overview'],
            $tour['top_highlights'], $tour['hotel_info'], $tour['vehicle_info'], $tour['flight_info'],
            $tour['includes'], $tour['excludes'], $tour['operator_id'], $tour['status'], $tour['is_featured'],
        ];

        if ($id) {
            db()->prepare('UPDATE tours SET title=?, category_id=?, budget_type=?, price=?, discount_percent=?, days=?, scheduled_date=?, min_pax=?, max_pax=?, short_overview=?, full_overview=?, top_highlights=?, hotel_info=?, vehicle_info=?, flight_info=?, includes=?, excludes=?, operator_id=?, status=?, is_featured=? WHERE id=?')
                ->execute([...$params, $id]);
        } else {
            db()->prepare('INSERT INTO tours (title, category_id, budget_type, price, discount_percent, days, scheduled_date, min_pax, max_pax, short_overview, full_overview, top_highlights, hotel_info, vehicle_info, flight_info, includes, excludes, operator_id, status, is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute($params);
            $id = (int) db()->lastInsertId();
        }

        $extraCategoryIds = array_values(array_diff($extraCategoryIds, [$tour['category_id']]));
        db()->prepare('DELETE FROM tour_extra_categories WHERE tour_id = ?')->execute([$id]);
        if ($extraCategoryIds) {
            $insertExtra = db()->prepare('INSERT INTO tour_extra_categories (tour_id, category_id) VALUES (?, ?)');
            foreach ($extraCategoryIds as $extraCategoryId) {
                $insertExtra->execute([$id, $extraCategoryId]);
            }
        }

        flash_set('success', 'Tour saved. Now add its destinations, activities and gallery below.');
        redirect('/admin/tours/manage.php?id=' . $id);
    }
}

$page_title = $id ? 'Edit tour' : 'Add tour';
$page_eyebrow = 'Safari';
$active_nav = 'tours';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <p class="section-title">Basics</p>
      <div class="form-grid">
        <div class="form-field form-field--full<?= isset($errors['title']) ? ' has-error' : '' ?>">
          <label for="title">Tour title</label>
          <input type="text" id="title" name="title" value="<?= h($tour['title']) ?>" placeholder="e.g. 5-Day Bwindi Gorilla &amp; Queen Elizabeth Safari" autofocus required>
          <?php if (isset($errors['title'])): ?><span class="error-text"><?= h($errors['title']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['category_id']) ? ' has-error' : '' ?>">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id" required>
            <option value="">Select&hellip;</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id'] ?>" <?= (int) $tour['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['category_id'])): ?><span class="error-text"><?= h($errors['category_id']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full">
          <label>Additional categories</label>
          <span class="hint" style="display:block;margin-bottom:8px;">Pick any other categories this tour also belongs to -- useful when a multi-day itinerary spans more than one, e.g. day 1 is gorilla trekking, day 6 is a wildlife game drive.</span>
          <div class="checkbox-grid">
            <?php foreach ($categories as $category): ?>
              <label class="checkbox-row">
                <input type="checkbox" name="extra_category_ids[]" value="<?= (int) $category['id'] ?>" <?= in_array((int) $category['id'], $extraCategoryIds, true) ? 'checked' : '' ?>>
                <?= h($category['name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-field">
          <label for="budget_type">Budget type</label>
          <select id="budget_type" name="budget_type">
            <?php foreach (['Luxury', 'Mid-Range', 'Budget'] as $opt): ?>
              <option value="<?= $opt ?>" <?= $tour['budget_type'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="draft" <?= $tour['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= $tour['status'] === 'published' ? 'selected' : '' ?>>Published</option>
          </select>
        </div>
        <div class="form-field">
          <label style="font-weight:400;"><input type="checkbox" name="is_featured" value="1" <?= $tour['is_featured'] ? 'checked' : '' ?>> Feature on the homepage</label>
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
        <div class="form-field<?= isset($errors['scheduled_date']) ? ' has-error' : '' ?>">
          <label for="scheduled_date">Scheduled date</label>
          <input type="date" id="scheduled_date" name="scheduled_date" value="<?= h((string) ($tour['scheduled_date'] ?? '')) ?>">
          <span class="hint">Optional. Set this to list the tour under Scheduled Tours with a fixed departure date.</span>
          <?php if (isset($errors['scheduled_date'])): ?><span class="error-text"><?= h($errors['scheduled_date']) ?></span><?php endif; ?>
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
        <div class="form-field">
          <label for="operator_id">Tour operator</label>
          <select id="operator_id" name="operator_id">
            <option value="">None yet</option>
            <?php foreach ($operators as $operator): ?>
              <option value="<?= (int) $operator['id'] ?>" <?= (int) $tour['operator_id'] === (int) $operator['id'] ? 'selected' : '' ?>><?= h($operator['company_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <p class="section-title" style="margin-top:26px;">Overview</p>
      <div class="form-grid">
        <div class="form-field form-field--full">
          <label for="short_overview">Short overview</label>
          <textarea id="short_overview" name="short_overview"><?= h($tour['short_overview']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="full_overview">Full overview</label>
          <span class="hint">Gallery for this overview is managed after saving.</span>
          <textarea id="full_overview" name="full_overview"><?= h($tour['full_overview']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="top_highlights">Top highlights</label>
          <textarea id="top_highlights" name="top_highlights"><?= h($tour['top_highlights']) ?></textarea>
        </div>
      </div>

      <p class="section-title" style="margin-top:26px;">Logistics</p>
      <div class="form-grid">
        <div class="form-field form-field--full">
          <label for="hotel_info">Hotel information</label>
          <span class="hint">Gallery managed after saving.</span>
          <textarea id="hotel_info" name="hotel_info"><?= h($tour['hotel_info']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="vehicle_info">Vehicle information</label>
          <span class="hint">Gallery managed after saving.</span>
          <textarea id="vehicle_info" name="vehicle_info"><?= h($tour['vehicle_info']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="flight_info">Flight information</label>
          <span class="hint">Gallery managed after saving.</span>
          <textarea id="flight_info" name="flight_info"><?= h($tour['flight_info']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="includes">Includes</label>
          <textarea id="includes" name="includes"><?= h($tour['includes']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <label for="excludes">Excludes</label>
          <textarea id="excludes" name="excludes"><?= h($tour['excludes']) ?></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save tour</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/tours/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
