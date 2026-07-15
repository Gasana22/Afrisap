<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/agents/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$returnTo = $_POST['return'] ?? '/admin/agents/index.php';

if (in_array($status, ['new', 'approved', 'rejected'], true)) {
    db()->prepare('UPDATE agents SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Application updated.');
}

header('Location: ' . $returnTo);
exit;
