<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/providers/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

db()->prepare('DELETE FROM service_providers WHERE id = ?')->execute([$id]);
flash_set('success', 'Provider deleted.');

redirect('/admin/providers/index.php');
