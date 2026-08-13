<?php

declare(strict_types=1);

function password_reset_mail_configuration_error(): ?string
{
    $mail = app_config('mail');
    if (!is_array($mail)) {
        return 'SMTP configuration is missing.';
    }

    $required = ['host', 'username', 'password', 'from_address'];
    foreach ($required as $field) {
        if (trim((string) ($mail[$field] ?? '')) === '') {
            return 'SMTP configuration is incomplete.';
        }
    }

    $port = filter_var($mail['port'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if ($port === false) {
        return 'SMTP port is invalid.';
    }
    if (!in_array($mail['encryption'] ?? null, ['tls', 'smtps'], true)) {
        return 'SMTP encryption must be tls or smtps.';
    }
    if (!filter_var($mail['from_address'] ?? null, FILTER_VALIDATE_EMAIL)) {
        return 'The password-reset sender address is invalid.';
    }
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return 'The SMTP mail dependency is unavailable.';
    }

    $secret = (string) app_config('reset_code_secret');
    if (strlen($secret) < 32) {
        return 'RESET_CODE_SECRET must contain at least 32 characters.';
    }

    return null;
}

function password_reset_mailer(): \PHPMailer\PHPMailer\PHPMailer
{
    $configurationError = password_reset_mail_configuration_error();
    if ($configurationError !== null) {
        throw new RuntimeException($configurationError);
    }

    $mail = app_config('mail');
    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = (string) $mail['host'];
    $mailer->Port = (int) $mail['port'];
    $mailer->SMTPAuth = true;
    $mailer->Username = (string) $mail['username'];
    $mailer->Password = (string) $mail['password'];
    $mailer->SMTPSecure = $mail['encryption'] === 'smtps'
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Timeout = 12;
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom((string) $mail['from_address'], (string) ($mail['from_name'] ?? 'BKK Community'));
    return $mailer;
}

function verify_password_reset_mail_transport(): void
{
    $mailer = password_reset_mailer();
    try {
        if (!$mailer->smtpConnect()) {
            throw new RuntimeException('SMTP authentication failed.');
        }
    } finally {
        $mailer->smtpClose();
    }
}

function send_password_reset_code(string $recipient, string $code): void
{
    $mailer = password_reset_mailer();
    $mailer->addAddress($recipient);
    $mailer->Subject = 'Your BKK Community password reset code';
    $mailer->Body = "Your BKK Community password reset code is: {$code}\n\n"
        . "This code expires in 15 minutes. If you did not request it, you can ignore this email.";
    $mailer->AltBody = $mailer->Body;
    $mailer->send();
}
