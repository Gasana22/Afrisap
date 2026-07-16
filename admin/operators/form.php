<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/uploads.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$operator = [
    'company_name' => '', 'phone' => '', 'email' => '', 'logo_path' => null,
    'contact_person' => '', 'office_location' => '', 'website' => '', 'profile_image_path' => null,
    'tour_type' => '', 'member_of' => '', 'trip_advisor_link' => '',
    'budget_type' => '', 'years_experience' => '', 'country_id' => '', 'overview' => '',
];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM tour_operators WHERE id = ?');
    $stmt->execute([$id]);
    $operator = $stmt->fetch();
    if (!$operator) {
        flash_set('error', 'That operator no longer exists.');
        redirect('/admin/operators/index.php');
    }
}

$countries = db()->query('SELECT id, name FROM countries ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $operator['company_name'] = trim($_POST['company_name'] ?? '');
    $operator['phone'] = trim($_POST['phone'] ?? '');
    $operator['email'] = trim($_POST['email'] ?? '');
    $operator['contact_person'] = trim($_POST['contact_person'] ?? '');
    $operator['office_location'] = trim($_POST['office_location'] ?? '');
    $operator['website'] = external_url($_POST['website'] ?? '');
    $operator['tour_type'] = trim($_POST['tour_type'] ?? '');
    $operator['member_of'] = trim($_POST['member_of'] ?? '');
    $operator['trip_advisor_link'] = external_url($_POST['trip_advisor_link'] ?? '');
    $operator['budget_type'] = $_POST['budget_type'] ?? '';
    $operator['years_experience'] = trim($_POST['years_experience'] ?? '');
    $operator['country_id'] = (int) ($_POST['country_id'] ?? 0);
    $operator['overview'] = trim($_POST['overview'] ?? '');

    if ($operator['company_name'] === '') {
        $errors['company_name'] = 'Enter a company name.';
    }
    if ($operator['budget_type'] !== '' && !in_array($operator['budget_type'], ['Luxury', 'Mid-Range', 'Budget'], true)) {
        $errors['budget_type'] = 'Choose a valid budget type.';
    }
    if ($operator['years_experience'] !== '' && (!ctype_digit($operator['years_experience']) || (int) $operator['years_experience'] > 100)) {
        $errors['years_experience'] = 'Enter a whole number of years.';
    }

    $logoPath = null;
    try {
        $logoPath = handle_image_upload('logo');
    } catch (RuntimeException $e) {
        $errors['logo'] = $e->getMessage();
    }

    $profileImagePath = null;
    try {
        $profileImagePath = handle_image_upload('profile_image');
    } catch (RuntimeException $e) {
        $errors['profile_image'] = $e->getMessage();
    }

    if (!$errors) {
        if ($logoPath !== null) {
            $operator['logo_path'] = $logoPath;
        }
        if ($profileImagePath !== null) {
            $operator['profile_image_path'] = $profileImagePath;
        }

        $budgetType = $operator['budget_type'] !== '' ? $operator['budget_type'] : null;
        $yearsExperience = $operator['years_experience'] !== '' ? (int) $operator['years_experience'] : null;
        $countryId = $operator['country_id'] ?: null;

        $params = [
            $operator['company_name'], $operator['logo_path'], $operator['phone'], $operator['email'],
            $operator['contact_person'] ?: null, $operator['office_location'] ?: null, $operator['website'] ?: null, $operator['profile_image_path'],
            $operator['tour_type'] ?: null, $operator['member_of'] ?: null, $operator['trip_advisor_link'] ?: null,
            $budgetType, $yearsExperience, $countryId, $operator['overview'] ?: null,
        ];

        if ($id) {
            $stmt = db()->prepare('UPDATE tour_operators SET company_name = ?, logo_path = ?, phone = ?, email = ?,
                contact_person = ?, office_location = ?, website = ?, profile_image_path = ?, tour_type = ?, member_of = ?,
                trip_advisor_link = ?, budget_type = ?, years_experience = ?, country_id = ?, overview = ?
                WHERE id = ?');
            $stmt->execute([...$params, $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO tour_operators (company_name, logo_path, phone, email,
                contact_person, office_location, website, profile_image_path, tour_type, member_of, trip_advisor_link,
                budget_type, years_experience, country_id, overview)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute($params);
        }
        flash_set('success', 'Operator saved.');
        redirect('/admin/operators/index.php');
    }
}

$page_title = $id ? 'Edit operator' : 'Add operator';
$page_eyebrow = 'Lookups';
$active_nav = 'operators';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['company_name']) ? ' has-error' : '' ?>">
          <label for="company_name">Operator name</label>
          <input type="text" id="company_name" name="company_name" value="<?= h($operator['company_name']) ?>" autofocus required>
          <?php if (isset($errors['company_name'])): ?><span class="error-text"><?= h($errors['company_name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="contact_person">Contact person name</label>
          <input type="text" id="contact_person" name="contact_person" value="<?= h($operator['contact_person']) ?>">
        </div>
        <div class="form-field">
          <label for="office_location">Office location</label>
          <input type="text" id="office_location" name="office_location" value="<?= h($operator['office_location']) ?>" placeholder="e.g. Kampala, Uganda">
        </div>
        <div class="form-field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= h($operator['email']) ?>">
        </div>
        <div class="form-field">
          <label for="phone">Contact phone</label>
          <input type="text" id="phone" name="phone" value="<?= h($operator['phone']) ?>">
        </div>
        <div class="form-field">
          <label for="website">Website link</label>
          <input type="url" id="website" name="website" value="<?= h($operator['website']) ?>" placeholder="https://">
        </div>
        <div class="form-field">
          <label for="trip_advisor_link">TripAdvisor link</label>
          <input type="url" id="trip_advisor_link" name="trip_advisor_link" value="<?= h($operator['trip_advisor_link']) ?>" placeholder="https://">
        </div>
        <div class="form-field<?= isset($errors['logo']) ? ' has-error' : '' ?>">
          <label for="logo">Logo</label>
          <?php if ($operator['logo_path']): ?>
            <img src="<?= h(url('/' . $operator['logo_path'])) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:4px;border:1px solid var(--line);margin-bottom:6px;display:block;">
          <?php endif; ?>
          <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['logo'])): ?><span class="error-text"><?= h($errors['logo']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['profile_image']) ? ' has-error' : '' ?>">
          <label for="profile_image">Profile page image</label>
          <?php if ($operator['profile_image_path']): ?>
            <img src="<?= h(url('/' . $operator['profile_image_path'])) ?>" alt="" style="width:160px;height:100px;object-fit:cover;border-radius:6px;border:1px solid var(--line);margin-bottom:6px;display:block;">
          <?php endif; ?>
          <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp">
          <span class="hint">Shown at the top of the operator's public profile page (separate from the small logo).</span>
          <?php if (isset($errors['profile_image'])): ?><span class="error-text"><?= h($errors['profile_image']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="tour_type">Tour type</label>
          <input type="text" id="tour_type" name="tour_type" value="<?= h($operator['tour_type']) ?>" placeholder="e.g. Safari &amp; Cultural Tours">
        </div>
        <div class="form-field">
          <label for="member_of">Member of</label>
          <input type="text" id="member_of" name="member_of" value="<?= h($operator['member_of']) ?>" placeholder="e.g. AUTO, UTB, UWA">
        </div>
        <div class="form-field<?= isset($errors['budget_type']) ? ' has-error' : '' ?>">
          <label for="budget_type">Budget type</label>
          <select id="budget_type" name="budget_type">
            <option value="">Not specified</option>
            <option value="Luxury" <?= $operator['budget_type'] === 'Luxury' ? 'selected' : '' ?>>Luxury</option>
            <option value="Mid-Range" <?= $operator['budget_type'] === 'Mid-Range' ? 'selected' : '' ?>>Mid-Range</option>
            <option value="Budget" <?= $operator['budget_type'] === 'Budget' ? 'selected' : '' ?>>Budget</option>
          </select>
          <?php if (isset($errors['budget_type'])): ?><span class="error-text"><?= h($errors['budget_type']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['years_experience']) ? ' has-error' : '' ?>">
          <label for="years_experience">Years of experience</label>
          <input type="number" id="years_experience" name="years_experience" value="<?= h((string) $operator['years_experience']) ?>" min="0" max="100">
          <?php if (isset($errors['years_experience'])): ?><span class="error-text"><?= h($errors['years_experience']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="country_id">Destination country</label>
          <select id="country_id" name="country_id">
            <option value="">Not specified</option>
            <?php foreach ($countries as $country): ?>
              <option value="<?= (int) $country['id'] ?>" <?= (int) $operator['country_id'] === (int) $country['id'] ? 'selected' : '' ?>><?= h($country['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field form-field--full">
          <label for="overview">Overview of the tour company</label>
          <textarea id="overview" name="overview" rows="5"><?= h($operator['overview']) ?></textarea>
        </div>
        <div class="form-field form-field--full">
          <span class="hint">Tours assigned to this operator (via the tour form's Operator field) appear automatically on their public profile page.</span>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save operator</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/operators/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
