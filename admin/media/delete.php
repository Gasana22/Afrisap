<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$returnTo = $_POST['return'] ?? '/admin/index.php';

if ($id) {
    delete_media($id);
    flash_set('success', 'Image removed.');
}

header('Location: ' . $returnTo);
exit;
