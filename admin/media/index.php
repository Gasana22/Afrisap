<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

$entityType = $_GET['entity_type'] ?? '';
$entityId = (int) ($_GET['entity_id'] ?? 0);
$title = $_GET['title'] ?? 'Gallery';
$back = $_GET['back'] ?? '/admin/index.php';

if (!in_array($entityType, MEDIA_ENTITY_TYPES, true) || !$entityId) {
    http_response_code(400);
    exit('Invalid gallery request.');
}

$items = get_media($entityType, $entityId);

// The hero slideshow specifically is capped at 5 photos (client asked for
// "3-5 image upload") -- every other gallery on the site is unlimited.
$maxItems = $entityType === 'hero_slideshow' ? 5 : null;
$atLimit = $maxItems !== null && count($items) >= $maxItems;

$page_title = $title;
$page_eyebrow = 'Gallery';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($entityType === 'hero_slideshow'): ?>
  <p class="hint" style="margin-bottom:18px;">These rotate as a background slideshow on the homepage hero (up to 5 photos). Upload at least 3 for it to actually rotate; with 1 photo it just displays statically, same as the old single hero background.</p>
<?php endif; ?>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Add image</div>
  </div>
  <div class="panel__body">
    <?php if ($atLimit): ?>
      <p class="hint">You've reached the limit of <?= $maxItems ?> photos. Remove one below before adding another.</p>
      <div class="form-actions"><a class="btn btn--ghost" href="<?= h($back) ?>">Back</a></div>
    <?php else: ?>
      <form method="post" action="<?= h(url('/admin/media/upload.php')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="entity_type" value="<?= h($entityType) ?>">
        <input type="hidden" name="entity_id" value="<?= $entityId ?>">
        <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
        <div class="form-grid">
          <div class="form-field">
            <label for="image">Image (JPG, PNG or WEBP, up to 5MB)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" required>
          </div>
          <div class="form-field">
            <label for="caption">Caption (optional)</label>
            <input type="text" id="caption" name="caption">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn--primary">Upload</button>
          <a class="btn btn--ghost" href="<?= h($back) ?>">Back</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title"><?= count($items) ?> image<?= count($items) === 1 ? '' : 's' ?></div>
  </div>
  <div class="panel__body">
    <?php if (!$items): ?>
      <div class="empty-state">
        <div class="empty-state__title">No images yet</div>
        <div class="empty-state__body">Upload the first one above.</div>
      </div>
    <?php else: ?>
      <div class="gallery-grid">
        <?php foreach ($items as $item): ?>
          <div class="gallery-item">
            <img src="<?= h(url('/' . $item['file_path'])) ?>" alt="<?= h($item['caption'] ?? '') ?>" loading="lazy">
            <?php if ($item['caption']): ?><div class="gallery-item__caption"><?= h($item['caption']) ?></div><?php endif; ?>
            <form method="post" action="<?= h(url('/admin/media/delete.php')) ?>" onsubmit="return confirm('Remove this image?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
              <button type="submit" class="btn btn--danger btn--sm gallery-item__delete">Remove</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
