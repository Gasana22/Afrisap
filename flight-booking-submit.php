<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/flight-booking.php');
}

csrf_verify();

$validFlightTypes = ['Internal Flights', 'Chartered Flights'];
$flightType = $_POST['flight_type'] ?? '';
$name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$travelDate = trim($_POST['travel_date'] ?? '') ?: null;
$message = trim($_POST['message'] ?? '');

if (!in_array($flightType, $validFlightTypes, true) || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/flight-booking.php');
}

db()->prepare('INSERT INTO flight_booking_requests (flight_type, full_name, email, phone, travel_date, message, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
    ->execute([$flightType, $name, $email, $phone, $travelDate, $message, 'new']);

send_email(
    $email,
    'We received your flight booking request',
    "Hi $name,\n\nThanks for your $flightType request. We'll put together a quote and get back to you shortly.\n\n— Safarisap"
);

notify_admin(
    'New flight booking request from ' . $name,
    "Flight type: $flightType\nName: $name\nEmail: $email\nPhone: $phone\nTravel date: " . ($travelDate ?? '-') . "\nMessage:\n$message"
);

redirect('/flight-booking.php?sent=1');
