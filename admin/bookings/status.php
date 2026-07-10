<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/bookings/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$returnTo = $_POST['return'] ?? '/admin/bookings/index.php';

if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
    db()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Booking updated.');
}

header('Location: ' . $returnTo);
exit;
