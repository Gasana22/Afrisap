<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Careers';
$page_eyebrow = 'About Us';
$active_nav = 'careers';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/careers/form.php')) . '">Add career</a>';

$careers = db()->query('SELECT c.*, (SELECT COUNT(*) FROM career_applications ca WHERE ca.career_id = c.id) AS application_count
    FROM careers c ORDER BY c.posted_at DESC')->fetchAll();

$careerStats = ['open' => 0, 'closed' => 0];
foreach ($careers as $career) {
    $careerStats[$career['status'] === 'open' ? 'open' : 'closed']++;
}
$totalApplications = array_sum(array_column($careers, 'application_count'));

require __DIR__ . '/../includes/header.php';
?>

<?php if ($careers): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('briefcase') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Career listings</div><div class="stat-tile__value"><?= count($careers) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Open</div><div class="stat-tile__value"><?= $careerStats['open'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Closed</div><div class="stat-tile__value"><?= $careerStats['closed'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--olive"><?= render_nav_glyph('users') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Applications</div><div class="stat-tile__value"><?= $totalApplications ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$careers): ?>
    <div class="empty-state">
      <div class="empty-state__title">No career listings yet</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/careers/form.php')) ?>">Add the first listing</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Title</th><th>Location</th><th>Status</th><th>Applications</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($careers as $career): ?>
          <tr>
            <td><?= h($career['title']) ?></td>
            <td class="table__meta"><?= h($career['location']) ?></td>
            <td><span class="status-pill status-pill--<?= $career['status'] === 'open' ? 'open' : 'draft' ?>"><?= h(ucfirst($career['status'])) ?></span></td>
            <td class="table__meta"><?= (int) $career['application_count'] ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/careers/form.php?id=' . $career['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/careers/delete.php')) ?>" onsubmit="return confirm('Delete this listing?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $career['id'] ?>">
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
