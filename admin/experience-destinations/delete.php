<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/experience-destinations/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

foreach (get_media('experience_destination', $id) as $item) {
    delete_media((int) $item['id']);
}
$activityIds = db()->prepare('SELECT id FROM experience_destination_activities WHERE experience_destination_id = ?');
$activityIds->execute([$id]);
foreach ($activityIds->fetchAll() as $row) {
    foreach (get_media('experience_destination_activity', (int) $row['id']) as $item) {
        delete_media((int) $item['id']);
    }
}

try {
    db()->prepare('DELETE FROM experience_destinations WHERE id = ?')->execute([$id]);
    flash_set('success', 'Destination deleted.');
} catch (PDOException $e) {
    flash_set('error', 'Can\'t delete this destination while experience tours still reference it.');
}

redirect('/admin/experience-destinations/index.php');
