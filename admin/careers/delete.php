<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/careers/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

db()->prepare('DELETE FROM careers WHERE id = ?')->execute([$id]);
flash_set('success', 'Listing deleted.');

redirect('/admin/careers/index.php');
