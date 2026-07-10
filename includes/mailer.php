<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/**
 * Sends a plain-text email via PHP's built-in mail(). This works out of the
 * box on most shared hosting (cPanel etc.) with zero extra setup, which is
 * why it's the default here rather than pulling in an SMTP library -- but on
 * a bare VPS with no local MTA configured, mail() will silently fail. If
 * that turns out to be the deployment target, swap this function's body for
 * an SMTP client (e.g. PHPMailer) without touching any of the call sites.
 *
 * Never throws: a broken mail transport should not break the form
 * submission that triggered it (the database row is the source of truth).
 * Every attempt is logged so failures are at least visible in the PHP
 * error log rather than silently disappearing.
 */
function send_email(string $to, string $subject, string $textBody, ?string $replyTo = null): bool
{
    $headers = [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
    ];
    if ($replyTo !== null && $replyTo !== '') {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    error_log(sprintf('[mail] sending to=%s subject=%s', $to, $subject));

    try {
        $sent = mail($to, $subject, $textBody, implode("\r\n", $headers));
    } catch (\Throwable $e) {
        error_log('[mail] send failed: ' . $e->getMessage());
        return false;
    }

    if (!$sent) {
        error_log("[mail] mail() returned false for to=$to subject=$subject");
    }

    return $sent;
}

function notify_admin(string $subject, string $textBody): void
{
    send_email(MAIL_ADMIN_ADDRESS, $subject, $textBody);
}
