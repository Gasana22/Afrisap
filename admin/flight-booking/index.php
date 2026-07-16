<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Flight Booking Requests';
$page_eyebrow = 'Inbox';
$active_nav = 'flight-booking';

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if (in_array($statusFilter, ['new', 'contacted', 'closed'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM flight_booking_requests' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="tab-nav">
  <a href="<?= h(url('/admin/flight-booking/index.php')) ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">All</a>
  <a href="<?= h(url('/admin/flight-booking/index.php?status=new')) ?>" class="<?= $statusFilter === 'new' ? 'is-active' : '' ?>">New</a>
  <a href="<?= h(url('/admin/flight-booking/index.php?status=contacted')) ?>" class="<?= $statusFilter === 'contacted' ? 'is-active' : '' ?>">Contacted</a>
  <a href="<?= h(url('/admin/flight-booking/index.php?status=closed')) ?>" class="<?= $statusFilter === 'closed' ? 'is-active' : '' ?>">Closed</a>
</div>

<div class="panel">
  <?php if (!$requests): ?>
    <div class="empty-state">
      <div class="empty-state__title">No flight booking requests yet</div>
      <div class="empty-state__body">Submissions from the Flight Booking page's quote form show up here.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Name</th><th>Flight type</th><th>Travel date</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $request): ?>
          <tr>
            <td><?= h($request['full_name']) ?><br><span class="table__meta"><?= h($request['email']) ?><?= $request['phone'] ? ' · ' . h($request['phone']) : '' ?></span></td>
            <td class="table__meta"><?= h($request['flight_type']) ?></td>
            <td class="table__meta"><?= $request['travel_date'] ? h(date('M j, Y', strtotime((string) $request['travel_date']))) : '&mdash;' ?></td>
            <td class="inbox-message"><?= h(mb_strimwidth((string) $request['message'], 0, 140, '…')) ?></td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($request['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= h(url('/admin/flight-booking/status.php')) ?>" class="inline-status-form">
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
