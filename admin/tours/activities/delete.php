<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/tours/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$tourId = (int) ($_POST['tour_id'] ?? 0);

foreach (get_media('tour_activity', $id) as $item) {
    delete_media((int) $item['id']);
}
db()->prepare('DELETE FROM tour_activities WHERE id = ? AND tour_id = ?')->execute([$id, $tourId]);
flash_set('success', 'Activity deleted.');

redirect('/admin/tours/manage.php?id=' . $tourId);
