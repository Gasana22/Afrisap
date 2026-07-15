<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/car-rental.php');
}

csrf_verify();

$validVehicleTypes = ['Safari 4X4 Landcruiser', 'VANs', 'Airport Transfer', 'Luxury Cars'];
$vehicleType = $_POST['vehicle_type'] ?? '';
$name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$pickupDate = trim($_POST['pickup_date'] ?? '') ?: null;
$dropoffDate = trim($_POST['dropoff_date'] ?? '') ?: null;
$message = trim($_POST['message'] ?? '');

if (!in_array($vehicleType, $validVehicleTypes, true) || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/car-rental.php');
}

db()->prepare('INSERT INTO car_rental_requests (vehicle_type, full_name, email, phone, pickup_date, dropoff_date, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$vehicleType, $name, $email, $phone, $pickupDate, $dropoffDate, $message, 'new']);

send_email(
    $email,
    'We received your car rental request',
    "Hi $name,\n\nThanks for your $vehicleType request. We'll put together a quote and get back to you shortly.\n\n— Safarisap"
);

notify_admin(
    'New car rental request from ' . $name,
    "Vehicle: $vehicleType\nName: $name\nEmail: $email\nPhone: $phone\nPickup: " . ($pickupDate ?? '-') . "\nDrop-off: " . ($dropoffDate ?? '-') . "\nMessage:\n$message"
);

redirect('/car-rental.php?sent=1');
