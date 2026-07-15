<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/uploads.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

$settings = db()->query('SELECT * FROM site_settings WHERE id = 1')->fetch();
if (!$settings) {
    db()->exec('INSERT INTO site_settings (id) VALUES (1)');
    $settings = db()->query('SELECT * FROM site_settings WHERE id = 1')->fetch();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $settings['hero_eyebrow'] = trim($_POST['hero_eyebrow'] ?? '') ?: null;
    $settings['hero_title'] = trim($_POST['hero_title'] ?? '') ?: null;
    $settings['hero_subtitle'] = trim($_POST['hero_subtitle'] ?? '') ?: null;

    $backgroundPath = null;
    try {
        $backgroundPath = handle_image_upload('hero_background');
    } catch (RuntimeException $e) {
        $errors['hero_background'] = $e->getMessage();
    }

    $removeBackground = isset($_POST['remove_hero_background']);

    if (!$errors) {
        if ($backgroundPath !== null) {
            $settings['hero_background_path'] = $backgroundPath;
        } elseif ($removeBackground) {
            $settings['hero_background_path'] = null;
        }

        db()->prepare('UPDATE site_settings SET hero_eyebrow = ?, hero_title = ?, hero_subtitle = ?, hero_background_path = ? WHERE id = 1')
            ->execute([$settings['hero_eyebrow'], $settings['hero_title'], $settings['hero_subtitle'], $settings['hero_background_path']]);
        flash_set('success', 'Homepage settings saved.');
        redirect('/admin/settings/index.php');
    }
}

$heroSlideshowCount = media_count('hero_slideshow', 1);

$page_title = 'Homepage Settings';
$page_eyebrow = 'Settings';
$active_nav = 'settings';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <p class="hint" style="margin-bottom:18px;">Leave a field blank to fall back to the default copy baked into the homepage. Use this to run seasonal promotions (e.g. a low-season discount message) without touching code.</p>
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full">
          <label for="hero_eyebrow">Hero eyebrow line</label>
          <input type="text" id="hero_eyebrow" name="hero_eyebrow" value="<?= h($settings['hero_eyebrow'] ?? '') ?>" placeholder="East Africa &middot; Uganda &middot; Kenya &middot; Tanzania &middot; Rwanda &middot; DR Congo">
        </div>
        <div class="form-field form-field--full">
          <label for="hero_title">Hero title</label>
          <input type="text" id="hero_title" name="hero_title" value="<?= h($settings['hero_title'] ?? '') ?>" placeholder="Explore. Experience. Belong.">
        </div>
        <div class="form-field form-field--full">
          <label for="hero_subtitle">Hero subtitle</label>
          <textarea id="hero_subtitle" name="hero_subtitle" style="min-height:100px;" placeholder="Gorilla treks through misty forest, a boat cruise past hippos..."><?= h($settings['hero_subtitle'] ?? '') ?></textarea>
        </div>
        <div class="form-field<?= isset($errors['hero_background']) ? ' has-error' : '' ?> form-field--full">
          <label for="hero_background">Hero background photo (single, fallback)</label>
          <?php if ($settings['hero_background_path']): ?>
            <div style="margin-bottom:8px;">
              <img src="<?= h(url('/' . $settings['hero_background_path'])) ?>" alt="" style="max-width:280px;border-radius:6px;display:block;margin-bottom:6px;">
              <label style="font-weight:400;"><input type="checkbox" name="remove_hero_background" value="1"> Remove current photo (use the drawn illustration instead)</label>
            </div>
          <?php endif; ?>
          <input type="file" id="hero_background" name="hero_background" accept="image/jpeg,image/png,image/webp">
          <span class="hint">Used only when the hero slideshow below is empty. If set, this replaces the drawn hero illustration.</span>
          <?php if (isset($errors['hero_background'])): ?><span class="error-text"><?= h($errors['hero_background']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save settings</button>
      </div>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Hero Slideshow</div>
  </div>
  <div class="panel__body">
    <p class="hint" style="margin-bottom:14px;">Upload 3&ndash;5 photos and the hero background rotates through them automatically instead of showing one static photo. Takes priority over the single background photo above when at least one is uploaded.</p>
    <a class="btn btn--primary btn--sm" href="<?= h(url('/admin/media/index.php?entity_type=hero_slideshow&entity_id=1&title=' . urlencode('Hero Slideshow') . '&back=' . urlencode('/admin/settings/index.php'))) ?>">Manage slideshow photos (<?= $heroSlideshowCount ?>)</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
