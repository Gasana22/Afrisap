<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
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
foreach ($bookings as &$booking) {
    $booking['cover'] = get_cover_image($booking['bookable_type'], (int) $booking['bookable_id']);
}
unset($booking);

// Stat tiles reflect the whole inbox regardless of the tab filter above.
$bookingStats = [
    'total' => (int) db()->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    'pending' => (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'confirmed' => (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
    'cancelled' => (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn(),
];

require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-tile stat-tile--hero">
    <div class="stat-tile__icon"><?= render_nav_glyph('calendar') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Total bookings</div><div class="stat-tile__value"><?= $bookingStats['total'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('mail') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Pending</div><div class="stat-tile__value"><?= $bookingStats['pending'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('tag') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Confirmed</div><div class="stat-tile__value"><?= $bookingStats['confirmed'] ?></div></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__icon stat-tile__icon--danger"><?= render_nav_glyph('compass') ?></div>
    <div class="stat-tile__body"><div class="stat-tile__label">Cancelled</div><div class="stat-tile__value"><?= $bookingStats['cancelled'] ?></div></div>
  </div>
</div>

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
              <div class="table-item">
                <?php if ($booking['cover']): ?>
                  <img class="table-item__thumb" src="<?= h(url('/' . $booking['cover'])) ?>" alt="">
                <?php endif; ?>
                <div>
                  <?php if ($booking['item_title']): ?>
                    <a href="<?= h(url($booking['item_link'])) ?>"><?= h($booking['item_title']) ?></a>
                  <?php else: ?>
                    <span class="table__meta">Deleted</span>
                  <?php endif; ?>
                  <br><span class="table__meta"><?= $booking['bookable_type'] === 'tour' ? 'Safari' : 'Experiential' ?></span>
                </div>
              </div>
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
