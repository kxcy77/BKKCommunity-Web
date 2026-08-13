<?php

declare(strict_types=1);

const PASSWORD_RESET_PUBLIC_MESSAGE = 'If an account exists with that email, a 6-digit password reset code has been sent.';
const PASSWORD_RESET_INVALID_MESSAGE = 'This reset code is invalid or has expired. Request a new code and try again.';
const PASSWORD_RESET_MAX_ATTEMPTS = 5;

function normalise_account_email(string $email): string
{
    return strtolower(trim($email));
}

function password_reset_code_hash(int $userId, string $email, string $code): string
{
    $secret = (string) app_config('reset_code_secret');
    if (strlen($secret) < 32) {
        throw new RuntimeException('Password-reset security is not configured.');
    }
    return hash_hmac('sha256', $userId . ':' . normalise_account_email($email) . ':' . $code, $secret);
}

function issue_password_reset_code(string $email): void
{
    $configurationError = password_reset_mail_configuration_error();
    if ($configurationError !== null) {
        throw new RuntimeException($configurationError);
    }

    $db = database();
    if (!$db) {
        throw new RuntimeException('The database is unavailable.');
    }

    // Authenticate the SMTP transport before looking up the account. This keeps
    // infrastructure failures uniform for known and unknown email addresses.
    verify_password_reset_mail_transport();

    $statement = $db->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
    $statement->execute([normalise_account_email($email)]);
    $userId = $statement->fetchColumn();
    if (!$userId) {
        return;
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $db->prepare('UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP() WHERE user_id = ? AND used_at IS NULL')
        ->execute([(int) $userId]);
    $insert = $db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, failed_attempts, expires_at) VALUES (?, ?, 0, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE))');
    $insert->execute([(int) $userId, password_reset_code_hash((int) $userId, $email, $code)]);
    $resetId = (int) $db->lastInsertId();

    try {
        send_password_reset_code(normalise_account_email($email), $code);
    } catch (Throwable $exception) {
        $db->prepare('UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$resetId]);
        throw $exception;
    }
}

function consume_password_reset_code(string $email, string $code, string $passwordHash): bool
{
    $email = normalise_account_email($email);
    $db = database();
    if (!$db) {
        throw new RuntimeException('The database is unavailable.');
    }

    $userStatement = $db->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
    $userStatement->execute([$email]);
    $userId = $userStatement->fetchColumn();
    if (!$userId) {
        return false;
    }

    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT id, token_hash, failed_attempts FROM password_reset_tokens
            WHERE user_id = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()
            ORDER BY created_at DESC LIMIT 1 FOR UPDATE');
        $statement->execute([(int) $userId]);
        $reset = $statement->fetch();
        if (!$reset || (int) $reset['failed_attempts'] >= PASSWORD_RESET_MAX_ATTEMPTS) {
            $db->commit();
            return false;
        }

        $expectedHash = password_reset_code_hash((int) $userId, $email, $code);
        if (!hash_equals((string) $reset['token_hash'], $expectedHash)) {
            $attempts = (int) $reset['failed_attempts'] + 1;
            $usedAt = $attempts >= PASSWORD_RESET_MAX_ATTEMPTS ? ', used_at = UTC_TIMESTAMP()' : '';
            $db->prepare("UPDATE password_reset_tokens SET failed_attempts = ?{$usedAt} WHERE id = ?")
                ->execute([$attempts, $reset['id']]);
            $db->commit();
            return false;
        }

        $completedAt = gmdate('Y-m-d H:i:s');
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$passwordHash, (int) $userId]);
        $db->prepare('UPDATE password_reset_tokens SET used_at = ? WHERE user_id = ? AND used_at IS NULL')
            ->execute([$completedAt, (int) $userId]);
        $db->prepare('UPDATE auth_sessions SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL')
            ->execute([$completedAt, (int) $userId]);
        $db->commit();
        return true;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}
