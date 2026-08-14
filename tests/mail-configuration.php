<?php

declare(strict_types=1);

$config = [
    'reset_code_secret' => str_repeat('x', 32),
    'mail' => [
        'resend_api_key' => 're_test_key',
        'from_address' => 'no-reply@example.com',
        'from_name' => 'BKK Community',
        'host' => '',
        'port' => '587',
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
    ],
];

function app_config(?string $key = null): mixed
{
    global $config;
    return $key === null ? $config : ($config[$key] ?? null);
}

function normalise_account_email(string $email): string
{
    return strtolower(trim($email));
}

require dirname(__DIR__) . '/app/mail.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL {$message}\n");
        exit(1);
    }
}

check(password_reset_uses_resend(), 'Resend transport selection');
check(password_reset_mail_configuration_error() === null, 'valid Resend configuration');

$payload = resend_password_reset_payload('member@example.net', '123456');
check($payload['from'] === 'BKK Community <no-reply@example.com>', 'sender construction');
check($payload['to'] === ['member@example.net'], 'recipient construction');
check(str_contains($payload['text'], '123456'), 'plain-text reset code');
check(str_contains($payload['html'], '123456'), 'HTML reset code');

check(resend_http_status(['HTTP/2 202']) === 202, 'successful HTTP status parsing');
check(resend_http_status(['HTTP/1.1 100 Continue', 'HTTP/2 429 Too Many Requests']) === 429, 'final HTTP status parsing');
check(resend_http_status([]) === 0, 'missing HTTP status handling');

$config['mail']['resend_api_key'] = 'not-a-resend-key';
check(password_reset_mail_configuration_error() === 'The Resend API key is invalid.', 'invalid key rejection');

echo "PASS Resend password-reset configuration and payload\n";
