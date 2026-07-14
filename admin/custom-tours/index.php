<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Custom Tour Requests';
$page_eyebrow = 'Inbox';
$active_nav = 'custom-tours';

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if (in_array($statusFilter, ['new', 'contacted', 'closed'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM custom_tour_requests' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Stat tiles reflect the whole inbox regardless of the tab filter above.
$customTourStats = [
    'total' => (int) db()->query('SELECT COUNT(*) FROM custom_tour_requests')->fetchColumn(),
    'new' => (int) db()->query("SELECT COUNT(*) FROM custom_tour_requests WHERE status = 'new'")->fetchColumn(),
    'contacted' => (int) db()->query("SELECT COUNT(*) FROM custom_tour_requests WHERE status = 'contacted'")->fetchColumn(),
    'closed' => (int) db()->query("SELECT COUNT(*) FROM custom_tour_requests WHERE status = 'closed'")->fetchColumn(),
];

require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('sliders') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Total requests</div><div class="stat-tile__value"><?= $customTourStats['total'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('mail') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">New</div><div class="stat-tile__value"><?= $customTourStats['new'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--olive"><?= render_nav_glyph('users') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Contacted</div><div class="stat-tile__value"><?= $customTourStats['contacted'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Closed</div><div class="stat-tile__value"><?= $customTourStats['closed'] ?></div></div>
  </div>
</div>

<div class="tab-nav">
  <a href="<?= h(url('/admin/custom-tours/index.php')) ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">All</a>
  <a href="<?= h(url('/admin/custom-tours/index.php?status=new')) ?>" class="<?= $statusFilter === 'new' ? 'is-active' : '' ?>">New</a>
  <a href="<?= h(url('/admin/custom-tours/index.php?status=contacted')) ?>" class="<?= $statusFilter === 'contacted' ? 'is-active' : '' ?>">Contacted</a>
  <a href="<?= h(url('/admin/custom-tours/index.php?status=closed')) ?>" class="<?= $statusFilter === 'closed' ? 'is-active' : '' ?>">Closed</a>
</div>

<div class="panel">
  <?php if (!$requests): ?>
    <div class="empty-state">
      <div class="empty-state__title">No custom tour requests yet</div>
      <div class="empty-state__body">Submissions from the "Create Your Own Tour" form show up here.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Name</th><th>Trip</th><th>Pax / Days / Budget</th><th>Preferences</th><th>Received</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $request): ?>
          <tr>
            <td><?= h($request['full_name']) ?><br><span class="table__meta"><?= h($request['email']) ?><?= $request['phone'] ? ' · ' . h($request['phone']) : '' ?></span></td>
            <td class="table__meta"><?= h($request['tour_name'] ?: '—') ?></td>
            <td class="table__meta"><?= (int) $request['pax'] ?> pax &middot; <?= (int) $request['days'] ?> days &middot; <?= h($request['budget_type']) ?></td>
            <td class="inbox-message">
              <?php
                $prefs = array_filter([$request['destinations'], $request['activities'], $request['experience_types']]);
                echo $prefs ? h(mb_strimwidth(implode(' · ', $prefs), 0, 140, '…')) : '<span style="opacity:.5;">None selected</span>';
              ?>
              <?php if ($request['notes']): ?><br><span class="table__meta"><?= h(mb_strimwidth($request['notes'], 0, 100, '…')) ?></span><?php endif; ?>
            </td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($request['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= h(url('/admin/custom-tours/status.php')) ?>" class="inline-status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="new" <?= $request['status'] === 'new' ? 'selected' : '' ?>>New</option>
                  <option value="contacted" <?= $request['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                  <option value="closed" <?= $request['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
