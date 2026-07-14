<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Experience Tours';
$page_eyebrow = 'Experiential';
$active_nav = 'experience-tours';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/experience-tours/form.php')) . '">Add experience tour</a>';

$tours = db()->query("SELECT et.id, et.title, et.price, et.days, et.status, ety.name AS type_name,
        (SELECT COUNT(*) FROM experience_tour_destinations etd WHERE etd.experience_tour_id = et.id) AS destination_count
    FROM experience_tours et
    JOIN experience_types ety ON ety.id = et.experience_type_id
    ORDER BY et.created_at DESC")->fetchAll();

$experienceTourStats = ['published' => 0, 'draft' => 0];
foreach ($tours as $tour) {
    $experienceTourStats[$tour['status'] === 'published' ? 'published' : 'draft']++;
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($tours): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('mask') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Experience tours</div><div class="stat-tile__value"><?= count($tours) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Published</div><div class="stat-tile__value"><?= $experienceTourStats['published'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Draft</div><div class="stat-tile__value"><?= $experienceTourStats['draft'] ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$tours): ?>
    <div class="empty-state">
      <div class="empty-state__title">No experience tours yet</div>
      <div class="empty-state__body">Add service providers and experience destinations first, then build a tour here.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/experience-tours/form.php')) ?>">Add the first one</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Title</th><th>Type</th><th>Price</th><th>Days</th><th>Destinations</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($tours as $tour): ?>
          <tr>
            <td><?= h($tour['title']) ?></td>
            <td class="table__meta"><?= h($tour['type_name']) ?></td>
            <td class="table__meta">$<?= number_format((float) $tour['price'], 2) ?></td>
            <td class="table__meta"><?= (int) $tour['days'] ?></td>
            <td class="table__meta"><?= (int) $tour['destination_count'] ?><?= (int) $tour['destination_count'] < 2 ? ' ⚠' : '' ?></td>
            <td><span class="status-pill status-pill--<?= h($tour['status']) ?>"><?= h(ucfirst($tour['status'])) ?></span></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-tours/manage.php?id=' . $tour['id'])) ?>">Manage</a>
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/experience-tours/form.php?id=' . $tour['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/experience-tours/delete.php')) ?>" onsubmit="return confirm('Delete this tour?');">
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
