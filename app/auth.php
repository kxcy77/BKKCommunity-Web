<?php

declare(strict_types=1);

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? null) === 'admin';
}

function require_login(): void
{
    if (!current_user()) {
        flash('info', 'Please log in to use that feature.');
        $_SESSION['return_after_login'] = ltrim(request_path(), '/');
        redirect_to('login.php');
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('error', 'Administrator access is required.');
        redirect_to('login.php');
    }
}

function attempt_login(string $email, string $password): bool
{
    $email = strtolower(trim($email));
    $db = database();

    if ($db) {
        $statement = $db->prepare('SELECT id, full_name, email, phone, role, password_hash,
            event_reminders_enabled AS event_reminders,
            discount_alerts_enabled AS discount_alerts
            FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        unset($user['password_hash']);
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        return true;
    }

    $registered = $_SESSION['demo_registered_users'][$email] ?? null;
    if (is_array($registered) && password_verify($password, $registered['password_hash'])) {
        unset($registered['password_hash']);
        session_regenerate_id(true);
        $_SESSION['user'] = $registered;
        return true;
    }

    $demos = [
        'member@bkk.demo' => ['full_name' => 'Thandiwe Nkosi', 'email' => 'member@bkk.demo', 'phone' => '082 123 4567', 'role' => 'member', 'password' => 'MemberDemo!26'],
        'admin@bkk.demo' => ['full_name' => 'Andrew (Demo Admin)', 'email' => 'admin@bkk.demo', 'phone' => '072 888 5030', 'role' => 'admin', 'password' => 'AdminDemo!26'],
    ];
    $user = $demos[$email] ?? null;
    if (!$user || !hash_equals($user['password'], $password)) {
        return false;
    }
    unset($user['password']);
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    return true;
}

function register_user(array $input): array
{
    $email = strtolower(trim($input['email']));
    $db = database();

    if ($db) {
        $check = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetchColumn()) {
            return [false, 'An account with that email address already exists.'];
        }
        try {
            $statement = $db->prepare('INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, \'member\')');
            $statement->execute([$input['full_name'], $email, $input['phone'], password_hash($input['password'], PASSWORD_DEFAULT)]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return [false, 'An account with that email address already exists.'];
            }
            throw $exception;
        }
        return [true, 'Your account was created. You can now log in.'];
    }

    if (isset($_SESSION['demo_registered_users'][$email]) || in_array($email, ['member@bkk.demo', 'admin@bkk.demo'], true)) {
        return [false, 'An account with that email address already exists in this demo session.'];
    }
    $_SESSION['demo_registered_users'][$email] = [
        'full_name' => $input['full_name'],
        'email' => $email,
        'phone' => $input['phone'],
        'role' => 'member',
        'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
    ];
    return [true, 'Your demo account was created for this browser session. You can now log in.'];
}

function logout_user(): void
{
    unset($_SESSION['user'], $_SESSION['rsvps']);
    session_regenerate_id(true);
}
