<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/activities/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

foreach (get_media('activity', $id) as $item) {
    delete_media((int) $item['id']);
}

db()->prepare('DELETE FROM activities WHERE id = ?')->execute([$id]);
flash_set('success', 'Activity deleted.');

redirect('/admin/activities/index.php');
