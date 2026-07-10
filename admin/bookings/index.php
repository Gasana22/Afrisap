<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Bookings';
$page_eyebrow = 'Inbox';
$active_nav = 'bookings';

$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if (in_array($statusFilter, ['pending', 'confirmed', 'cancelled'], true)) {
    $where = 'WHERE b.status = ?';
    $params[] = $statusFilter;
}

$bookings = db()->prepare("SELECT b.*,
        CASE b.bookable_type WHEN 'tour' THEN t.title ELSE et.title END AS item_title,
        CASE b.bookable_type WHEN 'tour' THEN CONCAT('/admin/tours/manage.php?id=', t.id) ELSE CONCAT('/admin/experience-tours/manage.php?id=', et.id) END AS item_link
    FROM bookings b
    LEFT JOIN tours t ON t.id = b.bookable_id AND b.bookable_type = 'tour'
    LEFT JOIN experience_tours et ON et.id = b.bookable_id AND b.bookable_type = 'experience_tour'
    $where
    ORDER BY b.created_at DESC");
$bookings->execute($params);
$bookings = $bookings->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="tab-nav">
  <a href="<?= h(url('/admin/bookings/index.php')) ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">All</a>
  <a href="<?= h(url('/admin/bookings/index.php?status=pending')) ?>" class="<?= $statusFilter === 'pending' ? 'is-active' : '' ?>">Pending</a>
  <a href="<?= h(url('/admin/bookings/index.php?status=confirmed')) ?>" class="<?= $statusFilter === 'confirmed' ? 'is-active' : '' ?>">Confirmed</a>
  <a href="<?= h(url('/admin/bookings/index.php?status=cancelled')) ?>" class="<?= $statusFilter === 'cancelled' ? 'is-active' : '' ?>">Cancelled</a>
</div>

<div class="panel">
  <?php if (!$bookings): ?>
    <div class="empty-state">
      <div class="empty-state__title">No bookings here yet</div>
      <div class="empty-state__body">Enquiries submitted from a tour or experience page will show up here.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Customer</th><th>Item</th><th>Travel date</th><th>People</th><th>Message</th><th>Received</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $booking): ?>
          <tr>
            <td>
              <?= h($booking['customer_name']) ?><br>
              <span class="table__meta"><?= h($booking['customer_email']) ?><?= $booking['customer_phone'] ? ' · ' . h($booking['customer_phone']) : '' ?></span>
            </td>
            <td>
              <?php if ($booking['item_title']): ?>
                <a href="<?= h(url($booking['item_link'])) ?>"><?= h($booking['item_title']) ?></a>
              <?php else: ?>
                <span class="table__meta">Deleted</span>
              <?php endif; ?>
              <br><span class="table__meta"><?= $booking['bookable_type'] === 'tour' ? 'Safari' : 'Experiential' ?></span>
            </td>
            <td class="table__meta"><?= h($booking['travel_date'] ?: '—') ?></td>
            <td class="table__meta"><?= (int) $booking['num_people'] ?></td>
            <td class="inbox-message"><?= h(mb_strimwidth((string) $booking['message'], 0, 100, '…')) ?></td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($booking['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= h(url('/admin/bookings/status.php')) ?>" class="inline-status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
                <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="pending" <?= $booking['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                  <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
