<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Pages';
$page_eyebrow = 'About Us';
$active_nav = 'pages';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/pages/form.php')) . '">Add page</a>';

$pages = db()->query('SELECT * FROM pages ORDER BY title')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($pages): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Content pages</div><div class="stat-tile__value"><?= count($pages) ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$pages): ?>
    <div class="empty-state">
      <div class="empty-state__title">No pages yet</div>
      <div class="empty-state__body">Content pages like About and Uganda Travel Tips live here.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/pages/form.php')) ?>">Add the first page</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Title</th><th>Slug</th><th>Updated</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($pages as $page): ?>
          <tr>
            <td><?= h($page['title']) ?></td>
            <td class="table__meta"><?= h($page['slug']) ?></td>
            <td class="table__meta"><?= h($page['updated_at']) ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/pages/form.php?id=' . $page['id'])) ?>">Edit</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
