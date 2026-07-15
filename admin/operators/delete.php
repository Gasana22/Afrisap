<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/operators/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

try {
    db()->prepare('DELETE FROM tour_operators WHERE id = ?')->execute([$id]);
    flash_set('success', 'Operator deleted.');
} catch (PDOException $e) {
    flash_set('error', 'Can\'t delete this operator while tours still reference it.');
}

redirect('/admin/operators/index.php');
