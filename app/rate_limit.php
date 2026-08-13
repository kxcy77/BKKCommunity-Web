<?php

declare(strict_types=1);

function trusted_client_identifier(): string
{
    $address = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    if (app_config('trust_proxy')) {
        $forwarded = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]);
        if (filter_var($forwarded, FILTER_VALIDATE_IP)) {
            $address = $forwarded;
        }
    }
    return filter_var($address, FILTER_VALIDATE_IP) ? $address : 'unknown';
}

function persistent_rate_limit_exceeded(string $scope, int $limit, int $windowSeconds, ?string $accountKey = null): bool
{
    $db = database();
    if (!$db) {
        return false;
    }

    $bucket = intdiv(time(), $windowSeconds);
    $identity = $scope . ':' . trusted_client_identifier() . ':'
        . ($accountKey === null ? '-' : hash('sha256', strtolower(trim($accountKey)))) . ':' . $bucket;
    $keyHash = hash('sha256', $identity);
    $expiresAt = gmdate('Y-m-d H:i:s', ($bucket + 1) * $windowSeconds);
    $statement = $db->prepare('INSERT INTO api_rate_limits (key_hash, scope, request_count, expires_at)
        VALUES (?, ?, 1, ?)
        ON DUPLICATE KEY UPDATE request_count = request_count + 1, updated_at = UTC_TIMESTAMP()');
    $statement->execute([$keyHash, $scope, $expiresAt]);
    $countStatement = $db->prepare('SELECT request_count FROM api_rate_limits WHERE key_hash = ?');
    $countStatement->execute([$keyHash]);
    return (int) $countStatement->fetchColumn() > $limit;
}

function rate_limit_retry_after(int $windowSeconds): int
{
    $bucket = intdiv(time(), $windowSeconds);
    return max(1, (($bucket + 1) * $windowSeconds) - time());
}
