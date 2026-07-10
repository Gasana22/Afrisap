<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/destinations/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

try {
    db()->prepare('DELETE FROM destinations WHERE id = ?')->execute([$id]);
    flash_set('success', 'Destination deleted.');
} catch (PDOException $e) {
    flash_set('error', 'Can\'t delete this destination while tours or activities still reference it.');
}

redirect('/admin/destinations/index.php');
