<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/tours/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

// Clean up galleries keyed directly to this tour.
foreach (['tour', 'tour_overview', 'tour_hotel', 'tour_vehicle', 'tour_flight'] as $type) {
    foreach (get_media($type, $id) as $item) {
        delete_media((int) $item['id']);
    }
}

// Clean up galleries keyed to this tour's activities (tour_activities cascades on tour delete,
// but their media rows don't since media has no FK).
$activityIds = db()->prepare('SELECT id FROM tour_activities WHERE tour_id = ?');
$activityIds->execute([$id]);
foreach ($activityIds->fetchAll() as $row) {
    foreach (get_media('tour_activity', (int) $row['id']) as $item) {
        delete_media((int) $item['id']);
    }
}

db()->prepare('DELETE FROM tours WHERE id = ?')->execute([$id]);
flash_set('success', 'Tour deleted.');

redirect('/admin/tours/index.php');
