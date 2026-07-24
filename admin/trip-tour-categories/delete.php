<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/trip-tour-categories/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

try {
    db()->prepare('DELETE FROM trip_tour_categories WHERE id = ?')->execute([$id]);
    flash_set('success', 'Category deleted.');
} catch (PDOException $e) {
    flash_set('error', 'Can\'t delete this category while trip tours still reference it.');
}

redirect('/admin/trip-tour-categories/index.php');
