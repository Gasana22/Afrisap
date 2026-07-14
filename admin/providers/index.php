<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Service Providers';
$page_eyebrow = 'Experiential';
$active_nav = 'providers';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/providers/form.php')) . '">Add provider</a>';

$providers = db()->query('SELECT p.*, et.name AS type_name
    FROM service_providers p
    JOIN experience_types et ON et.id = p.experience_type_id
    ORDER BY p.company_name')->fetchAll();

$providerTypeCount = count(array_unique(array_column($providers, 'type_name')));

require __DIR__ . '/../includes/header.php';
?>

<?php if ($providers): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('briefcase') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Service providers</div><div class="stat-tile__value"><?= count($providers) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('mask') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Experience types covered</div><div class="stat-tile__value"><?= $providerTypeCount ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$providers): ?>
    <div class="empty-state">
      <div class="empty-state__title">No service providers yet</div>
      <div class="empty-state__body">These are the companies running cultural, farm, sports, manufacturing and ghetto experiences.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/providers/form.php')) ?>">Add the first provider</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Company</th>
          <th>Type</th>
          <th>Region</th>
          <th>Contact</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($providers as $provider): ?>
          <tr>
            <td>
              <div class="table-item">
                <?php if ($provider['logo_path']): ?>
                  <img class="table-item__thumb" src="<?= h(url('/' . $provider['logo_path'])) ?>" alt="">
                <?php endif; ?>
                <span><?= h($provider['company_name']) ?></span>
              </div>
            </td>
            <td class="table__meta"><?= h($provider['type_name']) ?></td>
            <td class="table__meta"><?= h($provider['region']) ?></td>
            <td class="table__meta"><?= h($provider['contact_person']) ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/providers/form.php?id=' . $provider['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/providers/delete.php')) ?>" onsubmit="return confirm('Delete this provider?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $provider['id'] ?>">
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
