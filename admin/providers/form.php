<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/uploads.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$provider = ['company_name' => '', 'logo_path' => null, 'region' => '', 'experience_type_id' => '', 'contact_person' => '', 'email' => '', 'phone' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM service_providers WHERE id = ?');
    $stmt->execute([$id]);
    $provider = $stmt->fetch();
    if (!$provider) {
        flash_set('error', 'That provider no longer exists.');
        redirect('/admin/providers/index.php');
    }
}

$types = db()->query('SELECT id, name FROM experience_types ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $provider['company_name'] = trim($_POST['company_name'] ?? '');
    $provider['region'] = trim($_POST['region'] ?? '');
    $provider['experience_type_id'] = (int) ($_POST['experience_type_id'] ?? 0);
    $provider['contact_person'] = trim($_POST['contact_person'] ?? '');
    $provider['email'] = trim($_POST['email'] ?? '');
    $provider['phone'] = trim($_POST['phone'] ?? '');

    if ($provider['company_name'] === '') {
        $errors['company_name'] = 'Enter a company name.';
    }
    if (!$provider['experience_type_id']) {
        $errors['experience_type_id'] = 'Choose an experience type.';
    }

    $logoPath = null;
    try {
        $logoPath = handle_image_upload('logo');
    } catch (RuntimeException $e) {
        $errors['logo'] = $e->getMessage();
    }

    if (!$errors) {
        if ($logoPath !== null) {
            $provider['logo_path'] = $logoPath;
        }
        $params = [$provider['company_name'], $provider['logo_path'], $provider['region'], $provider['experience_type_id'], $provider['contact_person'], $provider['email'], $provider['phone']];
        if ($id) {
            db()->prepare('UPDATE service_providers SET company_name=?, logo_path=?, region=?, experience_type_id=?, contact_person=?, email=?, phone=? WHERE id=?')
                ->execute([...$params, $id]);
        } else {
            db()->prepare('INSERT INTO service_providers (company_name, logo_path, region, experience_type_id, contact_person, email, phone) VALUES (?,?,?,?,?,?,?)')
                ->execute($params);
        }
        flash_set('success', 'Provider saved.');
        redirect('/admin/providers/index.php');
    }
}

$page_title = $id ? 'Edit provider' : 'Add provider';
$page_eyebrow = 'Experiential';
$active_nav = 'providers';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['company_name']) ? ' has-error' : '' ?>">
          <label for="company_name">Company name</label>
          <input type="text" id="company_name" name="company_name" value="<?= h($provider['company_name']) ?>" autofocus required>
          <?php if (isset($errors['company_name'])): ?><span class="error-text"><?= h($errors['company_name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['experience_type_id']) ? ' has-error' : '' ?>">
          <label for="experience_type_id">Experience type</label>
          <select id="experience_type_id" name="experience_type_id" required>
            <option value="">Select&hellip;</option>
            <?php foreach ($types as $type): ?>
              <option value="<?= (int) $type['id'] ?>" <?= (int) $provider['experience_type_id'] === (int) $type['id'] ? 'selected' : '' ?>><?= h($type['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['experience_type_id'])): ?><span class="error-text"><?= h($errors['experience_type_id']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="region">Region</label>
          <input type="text" id="region" name="region" value="<?= h($provider['region']) ?>">
        </div>
        <div class="form-field<?= isset($errors['logo']) ? ' has-error' : '' ?>">
          <label for="logo">Logo</label>
          <?php if ($provider['logo_path']): ?>
            <img src="<?= h(url('/' . $provider['logo_path'])) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:4px;border:1px solid var(--line);margin-bottom:6px;">
          <?php endif; ?>
          <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['logo'])): ?><span class="error-text"><?= h($errors['logo']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="contact_person">Contact person</label>
          <input type="text" id="contact_person" name="contact_person" value="<?= h($provider['contact_person']) ?>">
        </div>
        <div class="form-field">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone" value="<?= h($provider['phone']) ?>">
        </div>
        <div class="form-field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= h($provider['email']) ?>">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save provider</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/providers/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
