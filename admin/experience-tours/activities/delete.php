<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/experience-tours/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$tourId = (int) ($_POST['tour_id'] ?? 0);

db()->prepare('DELETE FROM experience_tour_activities WHERE id = ? AND experience_tour_id = ?')->execute([$id, $tourId]);
flash_set('success', 'Activity removed.');

redirect('/admin/experience-tours/manage.php?id=' . $tourId);
