<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/trip-tours/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$tourId = (int) ($_POST['tour_id'] ?? 0);

foreach (get_media('trip_tour_activity', $id) as $item) {
    delete_media((int) $item['id']);
}
db()->prepare('DELETE FROM trip_tour_activities WHERE id = ? AND trip_tour_id = ?')->execute([$id, $tourId]);
flash_set('success', 'Activity deleted.');

redirect('/admin/trip-tours/manage.php?id=' . $tourId);
