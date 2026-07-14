<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Tours';
$page_eyebrow = 'Safari';
$active_nav = 'tours';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/tours/form.php')) . '">Add tour</a>';

$tours = db()->query("SELECT t.id, t.title, t.budget_type, t.price, t.days, t.scheduled_date, t.status,
        c.name AS category_name, o.company_name AS operator_name,
        (SELECT COUNT(*) FROM tour_destinations td WHERE td.tour_id = t.id) AS destination_count
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    LEFT JOIN tour_operators o ON o.id = t.operator_id
    ORDER BY t.created_at DESC")->fetchAll();

$tourStats = ['total' => count($tours), 'published' => 0, 'draft' => 0, 'upcoming' => 0];
$budgetCounts = ['Luxury' => 0, 'Mid-Range' => 0, 'Budget' => 0];
$today = date('Y-m-d');
foreach ($tours as $tour) {
    $tourStats[$tour['status'] === 'published' ? 'published' : 'draft']++;
    if ($tour['scheduled_date'] && $tour['scheduled_date'] >= $today) {
        $tourStats['upcoming']++;
    }
    if (isset($budgetCounts[$tour['budget_type']])) {
        $budgetCounts[$tour['budget_type']]++;
    }
}
$budgetTotal = max(1, array_sum($budgetCounts));

require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('compass') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Total tours</div><div class="stat-tile__value"><?= $tourStats['total'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Published</div><div class="stat-tile__value"><?= $tourStats['published'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Draft</div><div class="stat-tile__value"><?= $tourStats['draft'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--olive"><?= render_nav_glyph('calendar') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Upcoming departures</div><div class="stat-tile__value"><?= $tourStats['upcoming'] ?></div></div>
  </div>
</div>

<?php if ($tours): ?>
<div class="panel">
  <div class="panel__header"><div class="panel__title">Budget mix</div></div>
  <div class="panel__body">
    <div class="segment-bar">
      <div class="segment-bar__seg segment-bar__seg--a" style="width:<?= round($budgetCounts['Mid-Range'] / $budgetTotal * 100, 2) ?>%"></div>
      <div class="segment-bar__seg segment-bar__seg--b" style="width:<?= round($budgetCounts['Budget'] / $budgetTotal * 100, 2) ?>%"></div>
      <div class="segment-bar__seg segment-bar__seg--c" style="width:<?= round($budgetCounts['Luxury'] / $budgetTotal * 100, 2) ?>%"></div>
    </div>
    <div class="segment-legend">
      <span class="segment-legend__item"><i class="segment-legend__dot segment-legend__dot--a"></i>Mid-Range <span class="segment-legend__count"><?= $budgetCounts['Mid-Range'] ?></span></span>
      <span class="segment-legend__item"><i class="segment-legend__dot segment-legend__dot--b"></i>Budget <span class="segment-legend__count"><?= $budgetCounts['Budget'] ?></span></span>
      <span class="segment-legend__item"><i class="segment-legend__dot segment-legend__dot--c"></i>Luxury <span class="segment-legend__count"><?= $budgetCounts['Luxury'] ?></span></span>
    </div>
  </div>
</div>
<?php endif; ?>

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
          <th>Scheduled</th>
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
            <td class="table__meta"><?= $tour['scheduled_date'] ? h(formatDate($tour['scheduled_date'], 'M j, Y')) : '—' ?></td>
            <td class="table__meta"><?= (int) $tour['destination_count'] ?><?= (int) $tour['destination_count'] < 2 ? ' ⚠' : '' ?></td>
            <td><span class="status-pill status-pill--<?= h($tour['status']) ?>"><?= h(ucfirst($tour['status'])) ?></span></td>
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
