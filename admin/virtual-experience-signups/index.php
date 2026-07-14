<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$page_title = 'Virtual Experience Waitlist';
$page_eyebrow = 'Inbox';
$active_nav = 'virtual-experience-signups';

$signups = db()->query('SELECT * FROM virtual_experience_signups ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <?php if (!$signups): ?>
    <div class="empty-state">
      <div class="empty-state__title">No signups yet</div>
      <div class="empty-state__body">People interested in Virtual Experience (still coming soon) show up here once they leave their email on the public waitlist form.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Interested in</th><th>Received</th></tr></thead>
      <tbody>
        <?php foreach ($signups as $signup): ?>
          <tr>
            <td><?= h($signup['full_name']) ?></td>
            <td class="table__meta"><?= h($signup['email']) ?></td>
            <td class="inbox-message"><?= h($signup['notes'] ?: '—') ?></td>
            <td class="table__meta"><?= h(date('M j, Y', strtotime($signup['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
