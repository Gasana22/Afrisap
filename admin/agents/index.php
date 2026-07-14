<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Agent Applications';
$page_eyebrow = 'Inbox';
$active_nav = 'agents';

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if (in_array($statusFilter, ['new', 'approved', 'rejected'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM agents' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$agents = $stmt->fetchAll();

// Stat tiles reflect the whole inbox regardless of the tab filter above.
$agentStats = [
    'total' => (int) db()->query('SELECT COUNT(*) FROM agents')->fetchColumn(),
    'new' => (int) db()->query("SELECT COUNT(*) FROM agents WHERE status = 'new'")->fetchColumn(),
    'approved' => (int) db()->query("SELECT COUNT(*) FROM agents WHERE status = 'approved'")->fetchColumn(),
    'rejected' => (int) db()->query("SELECT COUNT(*) FROM agents WHERE status = 'rejected'")->fetchColumn(),
];

require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('users') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Total applications</div><div class="stat-tile__value"><?= $agentStats['total'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('mail') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">New</div><div class="stat-tile__value"><?= $agentStats['new'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Approved</div><div class="stat-tile__value"><?= $agentStats['approved'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--danger"><?= render_nav_glyph('compass') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Rejected</div><div class="stat-tile__value"><?= $agentStats['rejected'] ?></div></div>
  </div>
</div>

<div class="tab-nav">
  <a href="<?= h(url('/admin/agents/index.php')) ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">All</a>
  <a href="<?= h(url('/admin/agents/index.php?status=new')) ?>" class="<?= $statusFilter === 'new' ? 'is-active' : '' ?>">New</a>
  <a href="<?= h(url('/admin/agents/index.php?status=approved')) ?>" class="<?= $statusFilter === 'approved' ? 'is-active' : '' ?>">Approved</a>
  <a href="<?= h(url('/admin/agents/index.php?status=rejected')) ?>" class="<?= $statusFilter === 'rejected' ? 'is-active' : '' ?>">Rejected</a>
</div>

<div class="panel">
  <?php if (!$agents): ?>
    <div class="empty-state">
      <div class="empty-state__title">No agent applications yet</div>
      <div class="empty-state__body">Submissions from the "Join the Agent Pool" form show up here.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Name</th><th>Company</th><th>Region</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($agents as $agent): ?>
          <tr>
            <td><?= h($agent['full_name']) ?><br><span class="table__meta"><?= h($agent['email']) ?><?= $agent['phone'] ? ' · ' . h($agent['phone']) : '' ?></span></td>
            <td class="table__meta"><?= h($agent['company_name'] ?: '—') ?></td>
            <td class="table__meta"><?= h($agent['region'] ?: '—') ?></td>
            <td class="inbox-message"><?= h(mb_strimwidth((string) $agent['message'], 0, 100, '…')) ?></td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($agent['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= h(url('/admin/agents/status.php')) ?>" class="inline-status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $agent['id'] ?>">
                <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="new" <?= $agent['status'] === 'new' ? 'selected' : '' ?>>New</option>
                  <option value="approved" <?= $agent['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                  <option value="rejected" <?= $agent['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
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
