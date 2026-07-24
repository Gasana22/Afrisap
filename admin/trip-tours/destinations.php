<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/trip-tours/index.php');
}

csrf_verify();

$tourId = (int) ($_POST['tour_id'] ?? 0);
$destinationIds = array_map('intval', $_POST['destination_ids'] ?? []);
$destinationIds = array_unique(array_filter($destinationIds));

$pdo = db();
$pdo->beginTransaction();
$pdo->prepare('DELETE FROM trip_tour_destinations WHERE trip_tour_id = ?')->execute([$tourId]);
$insert = $pdo->prepare('INSERT INTO trip_tour_destinations (trip_tour_id, destination_id) VALUES (?, ?)');
foreach ($destinationIds as $destinationId) {
    $insert->execute([$tourId, $destinationId]);
}
$pdo->commit();

flash_set('success', 'Destinations updated.');
redirect('/admin/trip-tours/manage.php?id=' . $tourId);
