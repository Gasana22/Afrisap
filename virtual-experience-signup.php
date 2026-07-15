<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/virtual-experience.php');
}

csrf_verify();

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$notes = trim($_POST['notes'] ?? '') ?: null;

if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/virtual-experience.php');
}

db()->prepare('INSERT INTO virtual_experience_signups (full_name, email, notes) VALUES (?, ?, ?)')
    ->execute([$fullName, $email, $notes]);

send_email(
    $email,
    "You're on the Virtual Experience waitlist",
    "Hi $fullName,\n\nThanks for your interest in Safarisap Virtual Experience. We'll email you the moment it launches.\n\n— Safarisap"
);

notify_admin(
    'New Virtual Experience waitlist signup: ' . $fullName,
    "New waitlist signup\n\nName: $fullName\nEmail: $email" . ($notes ? "\nInterested in: $notes" : '')
);

redirect('/virtual-experience.php?sent=1');
