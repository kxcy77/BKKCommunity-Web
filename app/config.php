<?php

declare(strict_types=1);

function load_environment(string $file): void
{
    if (!is_file($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

load_environment(dirname(__DIR__) . '/.env');

return [
    'app_env' => env_value('APP_ENV', 'development'),
    'app_url' => rtrim((string) env_value('APP_URL', 'http://localhost:8080'), '/'),
    'base_path' => rtrim((string) env_value('APP_BASE_PATH', ''), '/'),
    'session_name' => env_value('APP_SESSION_NAME', 'bkk_community_session'),
    'trust_proxy' => filter_var(env_value('APP_TRUST_PROXY', 'false'), FILTER_VALIDATE_BOOL),
    'mail_from' => env_value('MAIL_FROM', ''),
    'db' => [
        'host' => env_value('DB_HOST', ''),
        'port' => env_value('DB_PORT', '3306'),
        'name' => env_value('DB_NAME', 'bkk_community'),
        'user' => env_value('DB_USER', 'bkk_app'),
        'password' => env_value('DB_PASSWORD', ''),
    ],
];
