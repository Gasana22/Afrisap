<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Tour Categories';
$page_eyebrow = 'Lookups';
$active_nav = 'categories';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/categories/form.php')) . '">Add category</a>';

$categories = db()->query("SELECT * FROM tour_categories ORDER BY FIELD(menu_group, 'safari','trip','school'), sort_order, name")->fetchAll();

$groupLabels = ['safari' => 'Safari Tours', 'trip' => 'Trip Tours', 'school' => 'School Trips'];
$groupCounts = ['safari' => 0, 'trip' => 0, 'school' => 0];
foreach ($categories as $category) {
    if (isset($groupCounts[$category['menu_group']])) {
        $groupCounts[$category['menu_group']]++;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($categories): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Total categories</div><div class="stat-tile__value"><?= count($categories) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('compass') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Safari Tours</div><div class="stat-tile__value"><?= $groupCounts['safari'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('sliders') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Trip Tours</div><div class="stat-tile__value"><?= $groupCounts['trip'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--olive"><?= render_nav_glyph('book') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">School Trips</div><div class="stat-tile__value"><?= $groupCounts['school'] ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$categories): ?>
    <div class="empty-state">
      <div class="empty-state__title">No tour categories yet</div>
      <div class="empty-state__body">Add sub-menu categories like Gorilla Trekking Safaris or Island Trips.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/categories/form.php')) ?>">Add the first category</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Menu</th>
          <th>Slug</th>
          <th>Order</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $category): ?>
          <tr>
            <td><?= h($category['name']) ?></td>
            <td class="table__meta"><?= h($groupLabels[$category['menu_group']] ?? $category['menu_group']) ?></td>
            <td class="table__meta"><?= h($category['slug']) ?></td>
            <td class="table__meta"><?= (int) $category['sort_order'] ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/categories/form.php?id=' . $category['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/categories/delete.php')) ?>" onsubmit="return confirm('Delete this category? This can\'t be undone.');">
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
