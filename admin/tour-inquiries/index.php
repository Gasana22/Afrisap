<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Tour Itinerary Inquiries';
$page_eyebrow = 'Inbox';
$active_nav = 'tour-inquiries';

$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if (in_array($statusFilter, ['new', 'contacted', 'closed'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM tour_itinerary_inquiries' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

$addOnsByInquiry = [];
if ($inquiries) {
    $ids = array_column($inquiries, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $addOnStmt = db()->prepare("SELECT inquiry_id, experience_tour_title FROM tour_itinerary_inquiry_addons WHERE inquiry_id IN ($placeholders) ORDER BY id");
    $addOnStmt->execute($ids);
    foreach ($addOnStmt->fetchAll() as $addOn) {
        $addOnsByInquiry[$addOn['inquiry_id']][] = $addOn['experience_tour_title'];
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="tab-nav">
  <a href="<?= h(url('/admin/tour-inquiries/index.php')) ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">All</a>
  <a href="<?= h(url('/admin/tour-inquiries/index.php?status=new')) ?>" class="<?= $statusFilter === 'new' ? 'is-active' : '' ?>">New</a>
  <a href="<?= h(url('/admin/tour-inquiries/index.php?status=contacted')) ?>" class="<?= $statusFilter === 'contacted' ? 'is-active' : '' ?>">Contacted</a>
  <a href="<?= h(url('/admin/tour-inquiries/index.php?status=closed')) ?>" class="<?= $statusFilter === 'closed' ? 'is-active' : '' ?>">Closed</a>
</div>

<div class="panel">
  <?php if (!$inquiries): ?>
    <div class="empty-state">
      <div class="empty-state__title">No tour itinerary inquiries yet</div>
      <div class="empty-state__body">Submissions from the "Enquire About This Tour" form on a tour page show up here, along with the itinerary details shown to the visitor at the time.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Visitor</th><th>Itinerary</th><th>Trip</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($inquiries as $inquiry): ?>
          <tr>
            <td>
              <?= h($inquiry['customer_name']) ?>
              <br><span class="table__meta"><?= h($inquiry['customer_email']) ?><?= $inquiry['whatsapp_number'] ? ' · WhatsApp: ' . h($inquiry['whatsapp_number']) : '' ?></span>
              <br><span class="table__meta"><?= $inquiry['country'] ? h($inquiry['country']) . ' · ' : '' ?><?= (int) $inquiry['num_visitors'] ?> visitor<?= (int) $inquiry['num_visitors'] === 1 ? '' : 's' ?></span>
            </td>
            <td class="table__meta">
              <?= h($inquiry['itinerary_no'] ?? '—') ?>
              <?php if ($inquiry['tour_price'] !== null): ?><br>$<?= number_format((float) $inquiry['tour_price'], 0) ?><?php endif; ?>
              <?php if ($inquiry['budget_type']): ?><br><?= h($inquiry['budget_type']) ?><?php endif; ?>
            </td>
            <td class="table__meta">
              <?php if ($inquiry['tour_id']): ?>
                <a href="<?= h(url('/admin/tours/manage.php?id=' . $inquiry['tour_id'])) ?>"><?= h($inquiry['tour_title']) ?></a>
              <?php else: ?>
                <?= h($inquiry['tour_title']) ?>
              <?php endif; ?>
              <?php if ($inquiry['operator_name']): ?><br>Operator: <?= h($inquiry['operator_name']) ?><?php endif; ?>
              <?php if ($inquiry['travel_date']): ?><br>Travel date: <?= h(date('M j, Y', strtotime((string) $inquiry['travel_date']))) ?><?php endif; ?>
              <?php if (!empty($addOnsByInquiry[$inquiry['id']])): ?><br>+ <?= h(implode(', ', $addOnsByInquiry[$inquiry['id']])) ?><?php endif; ?>
            </td>
            <td class="inbox-message"><?= h(mb_strimwidth((string) $inquiry['message'], 0, 140, '…')) ?></td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($inquiry['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= h(url('/admin/tour-inquiries/status.php')) ?>" class="inline-status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $inquiry['id'] ?>">
                <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="new" <?= $inquiry['status'] === 'new' ? 'selected' : '' ?>>New</option>
                  <option value="contacted" <?= $inquiry['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                  <option value="closed" <?= $inquiry['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
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
