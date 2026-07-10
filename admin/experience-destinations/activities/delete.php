<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/experience-destinations/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$destinationId = (int) ($_POST['destination_id'] ?? 0);

foreach (get_media('experience_destination_activity', $id) as $item) {
    delete_media((int) $item['id']);
}
db()->prepare('DELETE FROM experience_destination_activities WHERE id = ? AND experience_destination_id = ?')->execute([$id, $destinationId]);
flash_set('success', 'Activity deleted.');

redirect('/admin/experience-destinations/manage.php?id=' . $destinationId);
