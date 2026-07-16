<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/flight-booking/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$returnTo = $_POST['return'] ?? '/admin/flight-booking/index.php';

if (in_array($status, ['new', 'contacted', 'closed'], true)) {
    db()->prepare('UPDATE flight_booking_requests SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Flight booking request updated.');
}

header('Location: ' . $returnTo);
exit;
