<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/experiences.php');
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
    redirect('/experience-tour.php?id=' . $tourId);
}

$stmt = db()->prepare("SELECT id, title FROM experience_tours WHERE id = ? AND status = 'published'");
$stmt->execute([$tourId]);
$tour = $stmt->fetch();
if (!$tour) {
    redirect('/experiences.php');
}

db()->prepare('INSERT INTO bookings (bookable_type, bookable_id, customer_name, customer_email, customer_phone, travel_date, num_people, message, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute(['experience_tour', $tourId, $name, $email, $phone, $travelDate, $numPeople, $message, 'pending']);

send_email(
    $email,
    'We received your enquiry — ' . $tour['title'],
    "Hi $name,\n\nThanks for your enquiry about \"{$tour['title']}\". We'll be in touch shortly to confirm dates and availability.\n\nYour details:\nTravel date: " . ($travelDate ?: 'Not specified') . "\nPeople: $numPeople\n\n— Safarisap"
);

notify_admin(
    'New experiential tour enquiry: ' . $tour['title'],
    "New experiential tour enquiry\n\nTour: {$tour['title']}\nName: $name\nEmail: $email\nPhone: $phone\nTravel date: " . ($travelDate ?: 'Not specified') . "\nPeople: $numPeople\nMessage: $message"
);

redirect('/experience-tour.php?id=' . $tourId . '&sent=1');
