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

require __DIR__ . '/../includes/header.php';
?>

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
          <th></th>
          <th>Company</th>
          <th>Contact</th>
          <th>Tours</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($operators as $operator): ?>
          <tr>
            <td style="width:52px;">
              <?php if ($operator['logo_path']): ?>
                <img src="<?= h(url('/' . $operator['logo_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid var(--line);">
              <?php endif; ?>
            </td>
            <td><?= h($operator['company_name']) ?></td>
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
