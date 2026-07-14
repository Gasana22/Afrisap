<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/create-your-own-tour.php');
}

csrf_verify();

$tourName = trim($_POST['tour_name'] ?? '') ?: null;
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$pax = (int) ($_POST['pax'] ?? 0);
$days = (int) ($_POST['days'] ?? 0);
$budgetType = in_array($_POST['budget_type'] ?? '', ['Luxury', 'Mid-Range', 'Budget'], true) ? $_POST['budget_type'] : 'Mid-Range';
$notes = trim($_POST['notes'] ?? '') ?: null;

if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $pax < 1 || $days < 1) {
    redirect('/create-your-own-tour.php');
}

$destinationIds = array_map('intval', $_POST['destination_ids'] ?? []);
$activityIds = array_map('intval', $_POST['activity_ids'] ?? []);
$experienceTypeIds = array_map('intval', $_POST['experience_type_ids'] ?? []);

$destinationNames = [];
if ($destinationIds) {
    $placeholders = implode(',', array_fill(0, count($destinationIds), '?'));
    $stmt = db()->prepare("SELECT name FROM destinations WHERE id IN ($placeholders) ORDER BY name");
    $stmt->execute($destinationIds);
    $destinationNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$activityNames = [];
if ($activityIds) {
    $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
    $stmt = db()->prepare("SELECT name FROM activities WHERE id IN ($placeholders) ORDER BY name");
    $stmt->execute($activityIds);
    $activityNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$experienceTypeNames = [];
if ($experienceTypeIds) {
    $placeholders = implode(',', array_fill(0, count($experienceTypeIds), '?'));
    $stmt = db()->prepare("SELECT name FROM experience_types WHERE id IN ($placeholders) ORDER BY name");
    $stmt->execute($experienceTypeIds);
    $experienceTypeNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$destinationsText = $destinationNames ? implode(', ', $destinationNames) : null;
$activitiesText = $activityNames ? implode(', ', $activityNames) : null;
$experienceTypesText = $experienceTypeNames ? implode(', ', $experienceTypeNames) : null;

db()->prepare('INSERT INTO custom_tour_requests (tour_name, full_name, email, phone, pax, days, budget_type, destinations, activities, experience_types, notes, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$tourName, $fullName, $email, $phone, $pax, $days, $budgetType, $destinationsText, $activitiesText, $experienceTypesText, $notes, 'new']);

$summaryLines = [
    'Pax: ' . $pax,
    'Days: ' . $days,
    'Budget: ' . $budgetType,
];
if ($destinationsText) { $summaryLines[] = 'Destinations: ' . $destinationsText; }
if ($activitiesText) { $summaryLines[] = 'Activities: ' . $activitiesText; }
if ($experienceTypesText) { $summaryLines[] = 'Experiential mix: ' . $experienceTypesText; }
if ($notes) { $summaryLines[] = "Notes:\n" . $notes; }
$summary = implode("\n", $summaryLines);

send_email(
    $email,
    'We received your custom tour request',
    "Hi $fullName,\n\nThanks for telling us what you're picturing" . ($tourName ? " for \"$tourName\"" : '') . ". A consultant will review it and get back to you shortly to start building it together.\n\nWhat you told us:\n$summary\n\n— Safarisap"
);

notify_admin(
    'New custom tour request from ' . $fullName . ($tourName ? ' — ' . $tourName : ''),
    "New custom tour request\n\nName: $fullName\nEmail: $email\nPhone: $phone\n\n$summary"
);

redirect('/create-your-own-tour.php?sent=1');
