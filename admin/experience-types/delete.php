<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/experience-types/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

try {
    db()->prepare('DELETE FROM experience_types WHERE id = ?')->execute([$id]);
    flash_set('success', 'Experience type deleted.');
} catch (PDOException $e) {
    flash_set('error', 'Can\'t delete this experience type while an experience destination or tour still references it.');
}

redirect('/admin/experience-types/index.php');
