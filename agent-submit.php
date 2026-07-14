<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/agents.php');
}

csrf_verify();

$name = trim($_POST['full_name'] ?? '');
$company = trim($_POST['company_name'] ?? '') ?: null;
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$region = trim($_POST['region'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/agents.php');
}

db()->prepare('INSERT INTO agents (full_name, company_name, email, phone, region, message, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
    ->execute([$name, $company, $email, $phone, $region, $message, 'new']);

send_email(
    $email,
    'We received your agent pool application',
    "Hi $name,\n\nThanks for applying to join the Safarisap agent pool. We'll review your application and be in touch.\n\n— Safarisap"
);

notify_admin(
    'New agent pool application from ' . $name,
    "New agent pool application\n\nName: $name\nCompany: " . ($company ?: 'Not specified') . "\nEmail: $email\nPhone: $phone\nRegion: $region\nMessage: $message"
);

redirect('/agents.php?sent=1');
