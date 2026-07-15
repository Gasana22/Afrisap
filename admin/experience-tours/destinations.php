<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/experience-tours/index.php');
}

csrf_verify();

$tourId = (int) ($_POST['tour_id'] ?? 0);
$submittedIds = array_unique(array_filter(array_map('intval', $_POST['destination_ids'] ?? [])));

if (count($submittedIds) < 2) {
    flash_set('error', 'Select at least 2 destinations for this tour.');
    redirect('/admin/experience-tours/manage.php?id=' . $tourId);
}

$pdo = db();

$currentStmt = $pdo->prepare('SELECT experience_destination_id FROM experience_tour_destinations WHERE experience_tour_id = ?');
$currentStmt->execute([$tourId]);
$currentIds = array_map('intval', array_column($currentStmt->fetchAll(), 'experience_destination_id'));

$added = array_diff($submittedIds, $currentIds);

$pdo->beginTransaction();
$pdo->prepare('DELETE FROM experience_tour_destinations WHERE experience_tour_id = ?')->execute([$tourId]);
$insertDest = $pdo->prepare('INSERT INTO experience_tour_destinations (experience_tour_id, experience_destination_id) VALUES (?, ?)');
foreach ($submittedIds as $destId) {
    $insertDest->execute([$tourId, $destId]);
}

// Auto-populate this tour's activity list from newly-added destinations' activities.
// These become independent copies (source_activity_id kept for traceability) so the
// tour's copy can be edited or removed without touching the destination's own activities.
if ($added) {
    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM experience_tour_activities WHERE experience_tour_id = ?');
    $orderStmt->execute([$tourId]);
    $nextOrder = (int) $orderStmt->fetchColumn();

    $sourceStmt = $pdo->prepare('SELECT * FROM experience_destination_activities WHERE experience_destination_id = ? ORDER BY sort_order, id');
    $insertActivity = $pdo->prepare('INSERT INTO experience_tour_activities (experience_tour_id, source_activity_id, title, description, sort_order) VALUES (?, ?, ?, ?, ?)');

    foreach ($added as $destId) {
        $sourceStmt->execute([$destId]);
        foreach ($sourceStmt->fetchAll() as $source) {
            $insertActivity->execute([$tourId, $source['id'], $source['title'], $source['description'], $nextOrder]);
            $nextOrder++;
        }
    }
}

$pdo->commit();

flash_set('success', 'Destinations updated.' . ($added ? ' Activities from the new destination(s) were added below — edit or remove as needed.' : ''));
redirect('/admin/experience-tours/manage.php?id=' . $tourId);
