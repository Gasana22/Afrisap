<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Tour Operators';
$page_eyebrow = 'Lookups';
$active_nav = 'operators';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/operators/form.php')) . '">Add operator</a>';

$operators = db()->query('SELECT o.*, COUNT(t.id) AS tour_count
    FROM tour_operators o
    LEFT JOIN tours t ON t.operator_id = o.id
    GROUP BY o.id
    ORDER BY o.company_name')->fetchAll();

$totalOperatorTours = array_sum(array_column($operators, 'tour_count'));

require __DIR__ . '/../includes/header.php';
?>

<?php if ($operators): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('briefcase') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Tour operators</div><div class="stat-tile__value"><?= count($operators) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('compass') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Tours assigned</div><div class="stat-tile__value"><?= $totalOperatorTours ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$operators): ?>
    <div class="empty-state">
      <div class="empty-state__title">No tour operators yet</div>
      <div class="empty-state__body">Add the agent/operator companies that will be attached to tours and shown to customers when they book.</div>
      <a class="btn btn--primary" href="<?= h(url('/admin/operators/form.php')) ?>">Add the first operator</a>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Company</th>
          <th>Contact</th>
          <th>Tours</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($operators as $operator): ?>
          <tr>
            <td>
              <div class="table-item">
                <?php if ($operator['logo_path']): ?>
                  <img class="table-item__thumb" src="<?= h(url('/' . $operator['logo_path'])) ?>" alt="">
                <?php endif; ?>
                <span><?= h($operator['company_name']) ?></span>
              </div>
            </td>
            <td class="table__meta"><?= h($operator['phone']) ?><?= $operator['phone'] && $operator['email'] ? ' · ' : '' ?><?= h($operator['email']) ?></td>
            <td class="table__meta"><?= (int) $operator['tour_count'] ?></td>
            <td>
              <div class="table__actions">
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/operators/form.php?id=' . $operator['id'])) ?>">Edit</a>
                <form method="post" action="<?= h(url('/admin/operators/delete.php')) ?>" onsubmit="return confirm('Delete this operator?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $operator['id'] ?>">
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
