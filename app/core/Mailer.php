<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        $config = require __DIR__ . '/../config/config.php';
        $mail = $config['mail'];

        // In local/dev without real SMTP creds, log instead of failing hard.
        if (empty($mail['username']) || empty($mail['password'])) {
            Logger::info("MAIL (no SMTP configured) to={$toEmail} subject=\"{$subject}\" body={$htmlBody}");
            return true;
        }

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = $mail['host'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $mail['username'];
            $mailer->Password = $mail['password'];
            $mailer->SMTPSecure = $mail['encryption'];
            $mailer->Port = $mail['port'];

            $mailer->setFrom($mail['from_address'], $mail['from_name']);
            $mailer->addAddress($toEmail);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlBody;

            $mailer->send();
            return true;
        } catch (PHPMailerException $e) {
            Logger::error('Mail send failed: ' . $mailer->ErrorInfo);
            return false;
        }
    }
}
