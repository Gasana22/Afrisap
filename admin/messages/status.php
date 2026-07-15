<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/messages/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$returnTo = $_POST['return'] ?? '/admin/messages/index.php';

if (in_array($status, ['new', 'read', 'closed'], true)) {
    db()->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Message updated.');
}

header('Location: ' . $returnTo);
exit;
