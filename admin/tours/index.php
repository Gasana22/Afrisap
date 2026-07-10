<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Tours';
$page_eyebrow = 'Safari';
$active_nav = 'tours';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/tours/form.php')) . '">Add tour</a>';

$tours = db()->query("SELECT t.id, t.title, t.budget_type, t.price, t.days, t.status,
        c.name AS category_name, o.company_name AS operator_name,
        (SELECT COUNT(*) FROM tour_destinations td WHERE td.tour_id = t.id) AS destination_count
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    LEFT JOIN tour_operators o ON o.id = t.operator_id
    ORDER BY t.created_at DESC")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <?php if (!$tours): ?>
    <div class="empty-state">
      <div class="empty-state__title">No tours yet</div>
      <div class="empty-state__body">Add Countries, Tour Categories and Destinations first, then build your first tour itinerary here.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/tours/form.php')) ?>">Add the first tour</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Category</th>
          <th>Budget</th>
          <th>Price</th>
          <th>Days</th>
          <th>Destinations</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tours as $tour): ?>
          <tr>
            <td><?= h($tour['title']) ?></td>
            <td class="table__meta"><?= h($tour['category_name']) ?></td>
            <td class="table__meta"><?= h($tour['budget_type']) ?></td>
            <td class="table__meta">$<?= number_format((float) $tour['price'], 2) ?></td>
            <td class="table__meta"><?= (int) $tour['days'] ?></td>
            <td class="table__meta"><?= (int) $tour['destination_count'] ?><?= (int) $tour['destination_count'] < 2 ? ' ⚠' : '' ?></td>
            <td class="table__meta"><?= h(ucfirst($tour['status'])) ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/tours/manage.php?id=' . $tour['id'])) ?>">Manage</a>
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/tours/form.php?id=' . $tour['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/tours/delete.php')) ?>" onsubmit="return confirm('Delete this tour? This can\'t be undone.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $tour['id'] ?>">
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
