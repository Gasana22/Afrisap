<?php
/**
 * Email sending via PHPMailer. When MAIL_LOG_ONLY=true (the default for
 * local dev), messages are appended to logs/mail.log instead of being
 * sent, so the app is usable without real SMTP credentials.
 */

require_once ROOT_PATH . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $config = app_config()['mail'];

    if ($config['log_only']) {
        $line = sprintf(
            "[%s] To: %s <%s> | Subject: %s\n%s\n---\n",
            date('c'),
            $toName,
            $toEmail,
            $subject,
            strip_tags($htmlBody)
        );
        file_put_contents(LOGS_PATH . '/mail.log', $line, FILE_APPEND);

        return true;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];

        $mail->setFrom($config['from_address'], $config['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();

        return true;
    } catch (Exception $e) {
        app_log_error('Mail send failed: ' . $e->getMessage());

        return false;
    }
}
