<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/destinations/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$destinationId = (int) ($_POST['destination_id'] ?? 0);

db()->prepare('DELETE FROM destination_activities WHERE id = ? AND destination_id = ?')->execute([$id, $destinationId]);
flash_set('success', 'Activity deleted.');

redirect('/admin/destinations/manage.php?id=' . $destinationId);
