<?php

declare(strict_types=1);

const RESEND_EMAIL_ENDPOINT = 'https://api.resend.com/emails';

function password_reset_uses_resend(): bool
{
    $mail = app_config('mail');
    return is_array($mail) && trim((string) ($mail['resend_api_key'] ?? '')) !== '';
}

function password_reset_mail_configuration_error(): ?string
{
    $mail = app_config('mail');
    if (!is_array($mail)) {
        return 'Email configuration is missing.';
    }

    if (password_reset_uses_resend()) {
        if (!str_starts_with(trim((string) $mail['resend_api_key']), 're_')) {
            return 'The Resend API key is invalid.';
        }
        if (!filter_var($mail['from_address'] ?? null, FILTER_VALIDATE_EMAIL)) {
            return 'The password-reset sender address is invalid.';
        }
        if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
            return 'Secure HTTPS email delivery is unavailable in this PHP runtime.';
        }
        $secret = (string) app_config('reset_code_secret');
        return strlen($secret) >= 32 ? null : 'RESET_CODE_SECRET must contain at least 32 characters.';
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
    // Resend authenticates the HTTPS send itself. Unlike SMTP, there is no
    // connection handshake that should run before every reset request.
    if (password_reset_uses_resend()) {
        $configurationError = password_reset_mail_configuration_error();
        if ($configurationError !== null) {
            throw new RuntimeException($configurationError);
        }
        return;
    }

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
    if (password_reset_uses_resend()) {
        send_password_reset_code_with_resend($recipient, $code);
        return;
    }

    $mailer = password_reset_mailer();
    $mailer->addAddress($recipient);
    $mailer->Subject = 'Your BKK Community password reset code';
    $mailer->Body = "Your BKK Community password reset code is: {$code}\n\n"
        . "This code expires in 15 minutes. If you did not request it, you can ignore this email.";
    $mailer->AltBody = $mailer->Body;
    $mailer->send();
}

function resend_password_reset_payload(string $recipient, string $code): array
{
    $mail = app_config('mail');
    $fromAddress = (string) ($mail['from_address'] ?? '');
    $fromName = trim((string) ($mail['from_name'] ?? 'BKK Community'));
    $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return [
        'from' => ($fromName === '' ? 'BKK Community' : $fromName) . " <{$fromAddress}>",
        'to' => [$recipient],
        'subject' => 'Your BKK Community password reset code',
        'text' => "Your BKK Community password reset code is: {$code}\n\n"
            . 'This code expires in 15 minutes. If you did not request it, you can ignore this email.',
        'html' => '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:24px">'
            . '<h1 style="color:#1F4E79;font-size:24px">BKK Community</h1>'
            . '<p>Use this six-digit code to reset your password:</p>'
            . '<p style="font-size:36px;font-weight:700;letter-spacing:8px;color:#1F4E79">' . $safeCode . '</p>'
            . '<p>This code expires in 15 minutes. If you did not request it, you can ignore this email.</p>'
            . '</div>',
    ];
}

function resend_http_status(array $headers): int
{
    foreach (array_reverse($headers) as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', (string) $header, $matches)) {
            return (int) $matches[1];
        }
    }
    return 0;
}

function send_password_reset_code_with_resend(string $recipient, string $code): void
{
    $configurationError = password_reset_mail_configuration_error();
    if ($configurationError !== null) {
        throw new RuntimeException($configurationError);
    }

    $mail = app_config('mail');
    $payload = json_encode(resend_password_reset_payload($recipient, $code), JSON_THROW_ON_ERROR);
    $idempotencyKey = 'bkk-reset-' . hash_hmac(
        'sha256',
        normalise_account_email($recipient) . ':' . $code,
        (string) app_config('reset_code_secret')
    );

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", [
                'Authorization: Bearer ' . trim((string) $mail['resend_api_key']),
                'Content-Type: application/json',
                'Accept: application/json',
                'Idempotency-Key: ' . $idempotencyKey,
            ]),
            'content' => $payload,
            'timeout' => 12,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $response = @file_get_contents(RESEND_EMAIL_ENDPOINT, false, $context);
    $headers = $http_response_header ?? [];
    $status = resend_http_status($headers);
    $decoded = is_string($response) ? json_decode($response, true) : null;

    if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['id'])) {
        // Never log the recipient, reset code, API key, or provider response.
        throw new RuntimeException('The transactional email provider rejected the password-reset message.');
    }
}
