<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/quote.php');
}

csrf_verify();

$quoteType = ($_POST['quote_type'] ?? 'safari') === 'experiential' ? 'experiential' : 'safari';
$name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$details = trim($_POST['details'] ?? '');

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $details === '') {
    redirect('/quote.php?type=' . $quoteType);
}

db()->prepare('INSERT INTO quote_requests (quote_type, full_name, email, phone, details, status) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([$quoteType, $name, $email, $phone, $details, 'new']);

send_email(
    $email,
    'We received your ' . $quoteType . ' quote request',
    "Hi $name,\n\nThanks for telling us what you're planning. We'll put together a quote and get back to you within 48 hours.\n\nWhat you told us:\n$details\n\n— Safarisap"
);

notify_admin(
    'New ' . $quoteType . ' quote request from ' . $name,
    "New $quoteType quote request\n\nName: $name\nEmail: $email\nPhone: $phone\nDetails:\n$details"
);

redirect('/quote.php?type=' . $quoteType . '&sent=1');
