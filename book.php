<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tours.php');
}

csrf_verify();

$tourId = (int) ($_POST['tour_id'] ?? 0);
$name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['customer_email'] ?? '');
$phone = trim($_POST['customer_phone'] ?? '');
$travelDate = trim($_POST['travel_date'] ?? '') ?: null;
$numPeople = max(1, (int) ($_POST['num_people'] ?? 1));
$message = trim($_POST['message'] ?? '');

if (!$tourId || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/tour.php?id=' . $tourId);
}

$exists = db()->prepare("SELECT id FROM tours WHERE id = ? AND status = 'published'");
$exists->execute([$tourId]);
if (!$exists->fetch()) {
    redirect('/tours.php');
}

db()->prepare('INSERT INTO bookings (bookable_type, bookable_id, customer_name, customer_email, customer_phone, travel_date, num_people, message, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute(['tour', $tourId, $name, $email, $phone, $travelDate, $numPeople, $message, 'pending']);

redirect('/tour.php?id=' . $tourId . '&sent=1');
