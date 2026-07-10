<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

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

redirect('/agents.php?sent=1');
