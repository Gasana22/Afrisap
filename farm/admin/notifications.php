<?php
require_once __DIR__ . '/includes/auth-check.php';

$userId = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id')
            ->execute(['id' => $id, 'user_id' => $userId]);
    } elseif ($action === 'mark_all_read') {
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id')->execute(['user_id' => $userId]);
    }

    redirect('/admin/notifications.php');
}

$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 100');
$stmt->execute(['user_id' => $userId]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
require __DIR__ . '/includes/header.php';
?>

<h1>Notifications</h1>

<form method="POST" action="<?= BASE_URL ?>/admin/notifications.php" style="margin-bottom:1rem;">
    <input type="hidden" name="action" value="mark_all_read">
    <button type="submit" class="btn">Mark all read</button>
</form>

<table>
    <thead><tr><th></th><th>Title</th><th>Message</th><th>When</th><th></th></tr></thead>
    <tbody>
        <?php if (!$notifications): ?><tr><td colspan="5">No notifications yet.</td></tr><?php endif; ?>
        <?php foreach ($notifications as $n): ?>
            <tr style="<?= $n['is_read'] ? 'opacity:0.6;' : 'font-weight:600;' ?>">
                <td><?= e($n['type']) ?></td>
                <td><?= e($n['title']) ?></td>
                <td><?= e($n['message']) ?></td>
                <td><?= e($n['created_at']) ?></td>
                <td>
                    <?php if (!$n['is_read']): ?>
                        <form method="POST" action="<?= BASE_URL ?>/admin/notifications.php">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                            <button type="submit" class="btn" style="padding:0.3rem 0.6rem; font-size:0.8rem;">Mark read</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
