<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Career Applications';
$page_eyebrow = 'Inbox';
$active_nav = 'career-applications';

$applications = db()->query('SELECT ca.*, c.title AS career_title
    FROM career_applications ca
    JOIN careers c ON c.id = ca.career_id
    ORDER BY ca.created_at DESC')->fetchAll();

$rolesAppliedTo = count(array_unique(array_column($applications, 'career_title')));
$withCv = count(array_filter($applications, fn (array $app) => !empty($app['cv_path'])));

require __DIR__ . '/../includes/header.php';
?>

<?php if ($applications): ?>
<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('briefcase') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Total applications</div><div class="stat-tile__value"><?= count($applications) ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Roles applied to</div><div class="stat-tile__value"><?= $rolesAppliedTo ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('doc') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">With CV attached</div><div class="stat-tile__value"><?= $withCv ?></div></div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <?php if (!$applications): ?>
    <div class="empty-state">
      <div class="empty-state__title">No applications yet</div>
      <div class="empty-state__body">Applications submitted from a career listing show up here.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Applicant</th><th>Role</th><th>Cover letter</th><th>CV</th><th>Received</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $app): ?>
          <tr>
            <td><?= h($app['full_name']) ?><br><span class="table__meta"><?= h($app['email']) ?><?= $app['phone'] ? ' · ' . h($app['phone']) : '' ?></span></td>
            <td class="table__meta"><?= h($app['career_title']) ?></td>
            <td class="inbox-message"><?= h(mb_strimwidth((string) $app['cover_letter'], 0, 120, '…')) ?></td>
            <td>
              <?php if ($app['cv_path']): ?>
                <a class="btn btn--ghost btn--sm" href="<?= h(url('/' . $app['cv_path'])) ?>" target="_blank" rel="noopener">Download</a>
              <?php else: ?>
                <span class="table__meta">—</span>
              <?php endif; ?>
            </td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($app['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
