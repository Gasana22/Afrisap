<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

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

$stmt = db()->prepare("SELECT t.id, t.title, o.email AS operator_email
    FROM tours t LEFT JOIN tour_operators o ON o.id = t.operator_id
    WHERE t.id = ? AND t.status = 'published'");
$stmt->execute([$tourId]);
$tour = $stmt->fetch();
if (!$tour) {
    redirect('/tours.php');
}

db()->prepare('INSERT INTO bookings (bookable_type, bookable_id, customer_name, customer_email, customer_phone, travel_date, num_people, message, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute(['tour', $tourId, $name, $email, $phone, $travelDate, $numPeople, $message, 'pending']);

send_email(
    $email,
    'We received your enquiry — ' . $tour['title'],
    "Hi $name,\n\nThanks for your enquiry about \"{$tour['title']}\". We'll be in touch shortly to confirm dates and availability.\n\nYour details:\nTravel date: " . ($travelDate ?: 'Not specified') . "\nPeople: $numPeople\n\n— Safarisap"
);

$notifyBody = "New tour enquiry\n\nTour: {$tour['title']}\nName: $name\nEmail: $email\nPhone: $phone\nTravel date: " . ($travelDate ?: 'Not specified') . "\nPeople: $numPeople\nMessage: $message";
notify_admin('New tour enquiry: ' . $tour['title'], $notifyBody);
if ($tour['operator_email']) {
    send_email($tour['operator_email'], 'New enquiry for your tour: ' . $tour['title'], $notifyBody, $email);
}

redirect('/tour.php?id=' . $tourId . '&sent=1');
