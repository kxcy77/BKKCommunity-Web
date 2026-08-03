#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only run from the command line.\n");
    exit(1);
}

$db = database();
if (!$db) {
    fwrite(STDERR, "Database configuration is required. Set DB_HOST, DB_NAME, DB_USER and DB_PASSWORD first.\n");
    exit(1);
}

function prompt(string $label): string
{
    $value = readline($label);
    return trim($value === false ? '' : $value);
}

function prompt_secret(string $label): string
{
    fwrite(STDOUT, $label);
    $canHide = function_exists('shell_exec') && trim((string) shell_exec('command -v stty 2>/dev/null')) !== '';
    if ($canHide) {
        shell_exec('stty -echo');
    }
    try {
        $value = fgets(STDIN);
    } finally {
        if ($canHide) {
            shell_exec('stty echo');
        }
        fwrite(STDOUT, PHP_EOL);
    }
    return trim($value === false ? '' : $value);
}

$email = strtolower(prompt('Administrator email: '));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Enter a valid email address.\n");
    exit(1);
}

$lookup = $db->prepare('SELECT id, full_name FROM users WHERE email = ? LIMIT 1');
$lookup->execute([$email]);
$existing = $lookup->fetch();

if ($existing) {
    $confirmation = strtolower(prompt("Promote {$existing['full_name']} to administrator? Type yes: "));
    if ($confirmation !== 'yes') {
        fwrite(STDOUT, "No changes made.\n");
        exit(0);
    }
    $db->prepare("UPDATE users SET role = 'admin', deleted_at = NULL WHERE id = ?")->execute([$existing['id']]);
    fwrite(STDOUT, "Administrator access granted.\n");
    exit(0);
}

$name = prompt('Full name: ');
$phone = prompt('Phone number (optional): ');
$password = prompt_secret('Password (at least 10 characters, upper/lowercase and a number): ');
$strong = strlen($password) >= 10 && preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password) && preg_match('/\d/', $password);
if (mb_strlen($name) < 2 || !$strong) {
    fwrite(STDERR, "The name or password does not meet the requirements. No account was created.\n");
    exit(1);
}

$statement = $db->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'admin')");
$statement->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Administrator account created.\n");

