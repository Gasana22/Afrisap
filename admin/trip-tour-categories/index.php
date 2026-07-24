<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Trip Tour Categories';
$page_eyebrow = 'Lookups';
$active_nav = 'trip-tour-categories';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/trip-tour-categories/form.php')) . '">Add category</a>';

$categories = db()->query('SELECT * FROM trip_tour_categories ORDER BY sort_order, name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <?php if (!$categories): ?>
    <div class="empty-state">
      <div class="empty-state__title">No trip tour categories yet</div>
      <div class="empty-state__body">Add sub-menu categories like Island Trips or Camping Trips.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/trip-tour-categories/form.php')) ?>">Add the first category</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Slug</th>
          <th>Order</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $category): ?>
          <tr>
            <td><?= h($category['name']) ?></td>
            <td class="table__meta"><?= h($category['slug']) ?></td>
            <td class="table__meta"><?= (int) $category['sort_order'] ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/trip-tour-categories/form.php?id=' . $category['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/trip-tour-categories/delete.php')) ?>" onsubmit="return confirm('Delete this category? This can\'t be undone.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
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
