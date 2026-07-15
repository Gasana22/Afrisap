<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/experience-tours/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

foreach (['experience_tour', 'experience_tour_overview'] as $type) {
    foreach (get_media($type, $id) as $item) {
        delete_media((int) $item['id']);
    }
}

db()->prepare('DELETE FROM experience_tours WHERE id = ?')->execute([$id]);
flash_set('success', 'Tour deleted.');

redirect('/admin/experience-tours/index.php');
