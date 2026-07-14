<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Quote Requests';
$page_eyebrow = 'Inbox';
$active_nav = 'quotes';

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if (in_array($statusFilter, ['new', 'contacted', 'closed'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM quote_requests' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$quotes = $stmt->fetchAll();

// Stat tiles reflect the whole inbox regardless of the tab filter above.
$quoteStats = [
    'total' => (int) db()->query('SELECT COUNT(*) FROM quote_requests')->fetchColumn(),
    'new' => (int) db()->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'new'")->fetchColumn(),
    'safari' => (int) db()->query("SELECT COUNT(*) FROM quote_requests WHERE quote_type = 'safari'")->fetchColumn(),
    'experiential' => (int) db()->query("SELECT COUNT(*) FROM quote_requests WHERE quote_type = 'experiential'")->fetchColumn(),
];

require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Total requests</div><div class="stat-tile__value"><?= $quoteStats['total'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('mail') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">New</div><div class="stat-tile__value"><?= $quoteStats['new'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('compass') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Safari quotes</div><div class="stat-tile__value"><?= $quoteStats['safari'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--olive"><?= render_nav_glyph('mask') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Experiential quotes</div><div class="stat-tile__value"><?= $quoteStats['experiential'] ?></div></div>
  </div>
</div>

<div class="tab-nav">
  <a href="<?= h(url('/admin/quotes/index.php')) ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">All</a>
  <a href="<?= h(url('/admin/quotes/index.php?status=new')) ?>" class="<?= $statusFilter === 'new' ? 'is-active' : '' ?>">New</a>
  <a href="<?= h(url('/admin/quotes/index.php?status=contacted')) ?>" class="<?= $statusFilter === 'contacted' ? 'is-active' : '' ?>">Contacted</a>
  <a href="<?= h(url('/admin/quotes/index.php?status=closed')) ?>" class="<?= $statusFilter === 'closed' ? 'is-active' : '' ?>">Closed</a>
</div>

<div class="panel">
  <?php if (!$quotes): ?>
    <div class="empty-state">
      <div class="empty-state__title">No quote requests yet</div>
      <div class="empty-state__body">Submissions from the Safari and Experiential quote forms show up here.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Name</th><th>Type</th><th>Details</th><th>Received</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($quotes as $quote): ?>
          <tr>
            <td><?= h($quote['full_name']) ?><br><span class="table__meta"><?= h($quote['email']) ?><?= $quote['phone'] ? ' · ' . h($quote['phone']) : '' ?></span></td>
            <td><span class="status-pill status-pill--<?= h($quote['quote_type']) ?>"><?= h(ucfirst($quote['quote_type'])) ?></span></td>
            <td class="inbox-message"><?= h(mb_strimwidth((string) $quote['details'], 0, 140, '…')) ?></td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($quote['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= h(url('/admin/quotes/status.php')) ?>" class="inline-status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $quote['id'] ?>">
                <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="new" <?= $quote['status'] === 'new' ? 'selected' : '' ?>>New</option>
                  <option value="contacted" <?= $quote['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                  <option value="closed" <?= $quote['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
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
