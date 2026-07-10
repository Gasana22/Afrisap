<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/contact.php');
}

csrf_verify();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '') ?: null;
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    redirect('/contact.php');
}

db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([$name, $email, $phone, $subject, $message, 'new']);

send_email(
    $email,
    'We received your message',
    "Hi $name,\n\nThanks for reaching out. We'll reply within 24 hours.\n\nYour message:\n$message\n\n— Safarisap"
);

notify_admin(
    'New contact message from ' . $name . ($subject ? ': ' . $subject : ''),
    "New contact message\n\nName: $name\nEmail: $email\nPhone: $phone\nSubject: " . ($subject ?: 'Not specified') . "\nMessage:\n$message"
);

redirect('/contact.php?sent=1');
