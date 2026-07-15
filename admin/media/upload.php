<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/media.php';
require_once __DIR__ . '/../../includes/uploads.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/index.php');
}

csrf_verify();

$entityType = $_POST['entity_type'] ?? '';
$entityId = (int) ($_POST['entity_id'] ?? 0);
$returnTo = $_POST['return'] ?? '/admin/index.php';
$caption = trim($_POST['caption'] ?? '') ?: null;

if (!in_array($entityType, MEDIA_ENTITY_TYPES, true) || !$entityId) {
    http_response_code(400);
    exit('Invalid gallery request.');
}

try {
    $path = handle_image_upload('image');
    if ($path === null) {
        flash_set('error', 'Choose an image to upload.');
    } else {
        add_media($entityType, $entityId, $path, $caption);
        flash_set('success', 'Image uploaded.');
    }
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
}

header('Location: ' . $returnTo);
exit;
