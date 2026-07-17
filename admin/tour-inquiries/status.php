<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/tour-inquiries/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$returnTo = $_POST['return'] ?? '/admin/tour-inquiries/index.php';

if (in_array($status, ['new', 'contacted', 'closed'], true)) {
    db()->prepare('UPDATE tour_itinerary_inquiries SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Inquiry updated.');
}

header('Location: ' . $returnTo);
exit;
