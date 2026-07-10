<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Countries';
$page_eyebrow = 'Lookups';
$active_nav = 'countries';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/countries/form.php')) . '">Add country</a>';

$countries = db()->query('SELECT c.id, c.name, COUNT(d.id) AS destination_count
    FROM countries c
    LEFT JOIN destinations d ON d.country_id = c.id
    GROUP BY c.id, c.name
    ORDER BY c.name')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <?php if (!$countries): ?>
    <div class="empty-state">
      <div class="empty-state__title">No countries yet</div>
      <div class="empty-state__body">Add the East African countries (and DR Congo) that destinations and tours will be tagged with.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/countries/form.php')) ?>">Add the first country</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Destinations</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($countries as $country): ?>
          <tr>
            <td><?= h($country['name']) ?></td>
            <td class="table__meta"><?= (int) $country['destination_count'] ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/countries/form.php?id=' . $country['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/countries/delete.php')) ?>" onsubmit="return confirm('Delete this country? This can\'t be undone.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $country['id'] ?>">
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
