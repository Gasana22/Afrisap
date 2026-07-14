<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Contact Messages';
$page_eyebrow = 'Inbox';
$active_nav = 'messages';

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if (in_array($statusFilter, ['new', 'read', 'closed'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM contact_messages' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="tab-nav">
  <a href="<?= h(url('/admin/messages/index.php')) ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">All</a>
  <a href="<?= h(url('/admin/messages/index.php?status=new')) ?>" class="<?= $statusFilter === 'new' ? 'is-active' : '' ?>">New</a>
  <a href="<?= h(url('/admin/messages/index.php?status=read')) ?>" class="<?= $statusFilter === 'read' ? 'is-active' : '' ?>">Read</a>
  <a href="<?= h(url('/admin/messages/index.php?status=closed')) ?>" class="<?= $statusFilter === 'closed' ? 'is-active' : '' ?>">Closed</a>
</div>

<div class="panel">
  <?php if (!$messages): ?>
    <div class="empty-state">
      <div class="empty-state__title">No messages yet</div>
      <div class="empty-state__body">Submissions from the Contact Us form show up here.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($messages as $message): ?>
          <tr>
            <td><?= h($message['name']) ?><br><span class="table__meta"><?= h($message['email']) ?><?= $message['phone'] ? ' · ' . h($message['phone']) : '' ?></span></td>
            <td class="table__meta"><?= h($message['subject'] ?: '—') ?></td>
            <td class="inbox-message"><?= h(mb_strimwidth($message['message'], 0, 120, '…')) ?></td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($message['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= h(url('/admin/messages/status.php')) ?>" class="inline-status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="new" <?= $message['status'] === 'new' ? 'selected' : '' ?>>New</option>
                  <option value="read" <?= $message['status'] === 'read' ? 'selected' : '' ?>>Read</option>
                  <option value="closed" <?= $message['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
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
