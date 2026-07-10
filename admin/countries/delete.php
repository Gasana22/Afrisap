<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/countries/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

try {
    db()->prepare('DELETE FROM countries WHERE id = ?')->execute([$id]);
    flash_set('success', 'Country deleted.');
} catch (PDOException $e) {
    flash_set('error', 'Can\'t delete this country while destinations still reference it.');
}

redirect('/admin/countries/index.php');
