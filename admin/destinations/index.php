<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Destinations';
$page_eyebrow = 'Safari';
$active_nav = 'destinations';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/destinations/form.php')) . '">Add destination</a>';

$destinations = db()->query('SELECT d.id, d.name, c.name AS country_name
    FROM destinations d
    JOIN countries c ON c.id = d.country_id
    ORDER BY c.name, d.name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <?php if (!$destinations): ?>
    <div class="empty-state">
      <div class="empty-state__title">No destinations yet</div>
      <div class="empty-state__body">Add national parks and game reserves &mdash; tours and category pages link to these.</div>
      <?php
        $hasCountries = (int) db()->query('SELECT COUNT(*) FROM countries')->fetchColumn() > 0;
      ?>
      <?php if ($hasCountries): ?>
        <a class="btn btn--primary" href="<?= h(url('/admin/destinations/form.php')) ?>">Add the first destination</a>
      <?php else: ?>
        <a class="btn btn--primary" href="<?= h(url('/admin/countries/form.php')) ?>">Add a country first</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Country</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($destinations as $destination): ?>
          <tr>
            <td><?= h($destination['name']) ?></td>
            <td class="table__meta"><?= h($destination['country_name']) ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/destinations/form.php?id=' . $destination['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/destinations/delete.php')) ?>" onsubmit="return confirm('Delete this destination? This can\'t be undone.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $destination['id'] ?>">
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
