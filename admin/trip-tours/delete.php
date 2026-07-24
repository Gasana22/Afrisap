<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/trip-tours/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

foreach (['trip_tour', 'trip_tour_overview', 'trip_tour_hotel', 'trip_tour_vehicle', 'trip_tour_flight'] as $type) {
    foreach (get_media($type, $id) as $item) {
        delete_media((int) $item['id']);
    }
}

$activityIds = db()->prepare('SELECT id FROM trip_tour_activities WHERE trip_tour_id = ?');
$activityIds->execute([$id]);
foreach ($activityIds->fetchAll() as $row) {
    foreach (get_media('trip_tour_activity', (int) $row['id']) as $item) {
        delete_media((int) $item['id']);
    }
}

db()->prepare('DELETE FROM trip_tours WHERE id = ?')->execute([$id]);
flash_set('success', 'Tour deleted.');

redirect('/admin/trip-tours/index.php');
