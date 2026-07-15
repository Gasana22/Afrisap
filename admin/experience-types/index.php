<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Experience Types';
$page_eyebrow = 'Experiential';
$active_nav = 'experience-types';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/experience-types/form.php')) . '">Add experience type</a>';

$experienceTypes = db()->query('SELECT * FROM experience_types ORDER BY name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <?php if (!$experienceTypes): ?>
    <div class="empty-state">
      <div class="empty-state__title">No experience types yet</div>
      <div class="empty-state__body">Add the categories shown on the homepage, like Cultural or Farm Experience.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/experience-types/form.php')) ?>">Add the first experience type</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Slug</th>
          <th>Short description</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($experienceTypes as $type): ?>
          <tr>
            <td>
              <?php if ($type['image_path']): ?>
                <img src="<?= h(url('/' . $type['image_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid var(--line);">
              <?php else: ?>
                <span class="table__meta">&mdash;</span>
              <?php endif; ?>
            </td>
            <td><?= h($type['name']) ?></td>
            <td class="table__meta"><?= h($type['slug']) ?></td>
            <td class="table__meta"><?= h($type['short_description'] ?? '') ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-types/form.php?id=' . $type['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/experience-types/delete.php')) ?>" onsubmit="return confirm('Delete this experience type? This can\'t be undone.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $type['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
