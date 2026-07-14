<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/destinations/index.php');
}

csrf_verify();

$kind = $_POST['kind'] ?? '';
$destinationId = (int) ($_POST['destination_id'] ?? 0);
$action = $_GET['action'] ?? '';
$table = $kind === 'bird' ? 'destination_birds' : ($kind === 'animal' ? 'destination_animals' : null);

if (!$table || !$destinationId) {
    http_response_code(400);
    exit('Invalid request.');
}

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        db()->prepare("INSERT INTO {$table} (destination_id, name) VALUES (?, ?)")->execute([$destinationId, $name]);
        flash_set('success', ucfirst($kind) . ' added.');
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    db()->prepare("DELETE FROM {$table} WHERE id = ? AND destination_id = ?")->execute([$id, $destinationId]);
    flash_set('success', ucfirst($kind) . ' removed.');
}

redirect('/admin/destinations/manage.php?id=' . $destinationId);
