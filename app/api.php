<?php

declare(strict_types=1);

function api_respond(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function api_error(int $status, string $message, string $code): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function api_database(): PDO
{
    $db = database();
    if (!$db) {
        api_error(503, 'The BKK database is not configured. Nothing was saved.', 'database_unavailable');
    }
    return $db;
}

function api_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    try {
        $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        api_error(400, 'The request body must contain valid JSON.', 'invalid_json');
    }
    if (!is_array($decoded)) {
        api_error(400, 'The request body must be a JSON object.', 'invalid_json');
    }
    return $decoded;
}

function api_path(): string
{
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $marker = '/api/v1';
    $position = strpos($path, $marker);
    if ($position === false) {
        return '/';
    }
    $route = substr($path, $position + strlen($marker));
    $route = '/' . ltrim((string) $route, '/');
    return $route === '/' ? '/' : rtrim($route, '/');
}

function api_bearer_token(): ?string
{
    $header = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
    if ($header === '') {
        return null;
    }
    if (!preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', $header, $matches)) {
        api_error(401, 'The authentication token is invalid.', 'invalid_token');
    }
    return strtolower($matches[1]);
}

function api_current_user(bool $required = true): ?array
{
    $token = api_bearer_token();
    if ($token === null) {
        if ($required) {
            api_error(401, 'Please sign in to continue.', 'authentication_required');
        }
        return null;
    }

    $db = api_database();
    $statement = $db->prepare("SELECT u.id, u.full_name, u.email, u.phone, u.role,
        u.notifications_enabled, u.event_reminders_enabled, u.discount_alerts_enabled,
        s.id AS session_id
        FROM auth_sessions s
        INNER JOIN users u ON u.id = s.user_id
        WHERE s.token_hash = ? AND s.revoked_at IS NULL AND s.expires_at > UTC_TIMESTAMP()
          AND u.deleted_at IS NULL
        LIMIT 1");
    $statement->execute([hash('sha256', $token)]);
    $user = $statement->fetch();
    if (!$user) {
        api_error(401, 'Your session has expired. Please sign in again.', 'session_expired');
    }
    $db->prepare('UPDATE auth_sessions SET last_used_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$user['session_id']]);
    return $user;
}

function api_member(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'phone' => $user['phone'] === null ? null : (string) $user['phone'],
        'notifications_enabled' => (bool) $user['notifications_enabled'],
        'event_reminders_enabled' => (bool) $user['event_reminders_enabled'],
        'discount_alerts_enabled' => (bool) $user['discount_alerts_enabled'],
    ];
}

function api_issue_token(PDO $db, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $statement = $db->prepare('INSERT INTO auth_sessions (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY))');
    $statement->execute([$userId, hash('sha256', $token)]);
    return $token;
}

function api_password_is_strong(string $password): bool
{
    return strlen($password) >= 10 && strlen($password) <= 128
        && preg_match('/[a-z]/', $password)
        && preg_match('/[A-Z]/', $password)
        && preg_match('/\d/', $password);
}

function api_event(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'title' => (string) $row['title'],
        'description' => (string) $row['description'],
        'start_at' => (string) $row['start_at'],
        'end_at' => (string) $row['end_at'],
        'location' => (string) $row['location'],
        'directions' => $row['directions'] === null ? null : (string) $row['directions'],
        'category' => (string) $row['category'],
        'colour_hex' => (string) $row['colour_hex'],
        'is_attending' => (bool) $row['is_attending'],
    ];
}

function api_event_select(?int $userId): string
{
    $attendance = $userId === null
        ? '0 AS is_attending'
        : "EXISTS(SELECT 1 FROM attendance a WHERE a.event_id = e.id AND a.user_id = ? AND a.status = 'attending') AS is_attending";
    return "SELECT e.id, e.title, e.description,
        DATE_FORMAT(e.start_at, '%Y-%m-%dT%H:%i:%sZ') AS start_at,
        DATE_FORMAT(e.end_at, '%Y-%m-%dT%H:%i:%sZ') AS end_at,
        e.location, e.directions, c.name AS category, c.colour_hex, {$attendance}
        FROM events e INNER JOIN event_categories c ON c.id = e.category_id";
}

function api_discount(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'store_name' => (string) $row['store_name'],
        'title' => (string) $row['title'],
        'details' => (string) $row['details'],
        'eligibility' => (string) $row['eligibility'],
        'claim_instructions' => (string) $row['claim_instructions'],
        'category' => (string) $row['category'],
        'valid_from' => $row['valid_from'] === null ? null : (string) $row['valid_from'],
        'valid_until' => $row['valid_until'] === null ? null : (string) $row['valid_until'],
    ];
}

function api_register(): never
{
    $input = api_input();
    $name = trim((string) ($input['full_name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $phone = trim((string) ($input['phone'] ?? '')) ?: null;
    $password = (string) ($input['password'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        api_error(422, 'Enter your full name and a valid email address.', 'validation_error');
    }
    if ($phone !== null && mb_strlen($phone) > 30) {
        api_error(422, 'The phone number is too long.', 'validation_error');
    }
    if (!api_password_is_strong($password)) {
        api_error(422, 'Use at least 10 characters with upper- and lowercase letters and a number.', 'weak_password');
    }

    $db = api_database();
    $db->beginTransaction();
    try {
        $statement = $db->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'member')");
        $statement->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = (int) $db->lastInsertId();
        $token = api_issue_token($db, $userId);
        $db->commit();
    } catch (PDOException $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($exception->getCode() === '23000') {
            api_error(409, 'An account with that email address already exists.', 'email_exists');
        }
        throw $exception;
    }
    $user = [
        'id' => $userId, 'full_name' => $name, 'email' => $email, 'phone' => $phone,
        'notifications_enabled' => true, 'event_reminders_enabled' => true, 'discount_alerts_enabled' => true,
    ];
    api_respond(['user' => api_member($user), 'token' => $token], 201);
}

function api_login(): never
{
    $input = api_input();
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        api_error(422, 'Enter a valid email address and password.', 'validation_error');
    }
    $db = api_database();
    $statement = $db->prepare('SELECT id, full_name, email, phone, password_hash, notifications_enabled, event_reminders_enabled, discount_alerts_enabled FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        api_error(401, 'The email address or password was incorrect.', 'invalid_credentials');
    }
    unset($user['password_hash']);
    $token = api_issue_token($db, (int) $user['id']);
    api_respond(['user' => api_member($user), 'token' => $token]);
}

function api_forgot_password(): never
{
    $input = api_input();
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_error(422, 'Enter a valid email address.', 'validation_error');
    }
    $db = api_database();
    $mailFrom = trim((string) app_config('mail_from'));
    if ($mailFrom === '') {
        api_error(503, 'Password reset email is not configured. Please contact BKK Community support.', 'email_unavailable');
    }
    if ($mailFrom !== '') {
        $statement = $db->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$email]);
        $userId = $statement->fetchColumn();
        if ($userId) {
            $token = bin2hex(random_bytes(32));
            $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$userId]);
            $db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))')
                ->execute([$userId, hash('sha256', $token)]);
            $link = rtrim((string) app_config('app_url'), '/') . app_url('new-password.php?token=' . rawurlencode($token));
            $headers = "From: {$mailFrom}\r\nContent-Type: text/plain; charset=UTF-8";
            if (!mail($email, 'Reset your BKK Community password', "Use this link within one hour:\n{$link}", $headers)) {
                $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$userId]);
                error_log('BKK API password reset email could not be sent.');
            }
        }
    }
    api_respond(['message' => 'If the account exists, reset instructions have been sent.']);
}

function api_reset_password(): never
{
    $input = api_input();
    $token = strtolower(trim((string) ($input['token'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token) || !api_password_is_strong($password)) {
        api_error(422, 'The reset token or new password is invalid.', 'validation_error');
    }
    $db = api_database();
    $statement = $db->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP() LIMIT 1');
    $statement->execute([hash('sha256', $token)]);
    $reset = $statement->fetch();
    if (!$reset) {
        api_error(422, 'That reset link is invalid or has expired.', 'invalid_reset_token');
    }
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $db->prepare('UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$reset['id']]);
        $db->prepare('UPDATE auth_sessions SET revoked_at = UTC_TIMESTAMP() WHERE user_id = ? AND revoked_at IS NULL')->execute([$reset['user_id']]);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
    api_respond(['message' => 'Your password has been updated.']);
}

function api_events(): never
{
    $user = api_current_user(false);
    $userId = $user === null ? null : (int) $user['id'];
    $category = trim((string) ($_GET['category'] ?? ''));
    $sql = api_event_select($userId) . " WHERE e.status = 'published' AND e.end_at >= UTC_TIMESTAMP()";
    $params = $userId === null ? [] : [$userId];
    if ($category !== '') {
        $sql .= ' AND c.name = ?';
        $params[] = $category;
    }
    $sql .= ' ORDER BY e.start_at';
    $statement = api_database()->prepare($sql);
    $statement->execute($params);
    api_respond(array_map('api_event', $statement->fetchAll()));
}

function api_event_detail(int $eventId): never
{
    $user = api_current_user(false);
    $userId = $user === null ? null : (int) $user['id'];
    $statement = api_database()->prepare(api_event_select($userId) . " WHERE e.id = ? AND e.status = 'published' LIMIT 1");
    $params = $userId === null ? [$eventId] : [$userId, $eventId];
    $statement->execute($params);
    $event = $statement->fetch();
    if (!$event) {
        api_error(404, 'That event could not be found.', 'event_not_found');
    }
    api_respond(api_event($event));
}

function api_set_attendance(int $eventId): never
{
    $user = api_current_user();
    $input = api_input();
    $status = (string) ($input['status'] ?? '');
    if (!in_array($status, ['attending', 'cancelled'], true)) {
        api_error(422, 'Attendance status must be attending or cancelled.', 'validation_error');
    }
    $db = api_database();
    $check = $db->prepare("SELECT id FROM events WHERE id = ? AND status = 'published' AND end_at >= UTC_TIMESTAMP() LIMIT 1");
    $check->execute([$eventId]);
    if (!$check->fetchColumn()) {
        api_error(404, 'That upcoming event could not be found.', 'event_not_found');
    }
    $statement = $db->prepare("INSERT INTO attendance (user_id, event_id, status, confirmed_at)
        VALUES (?, ?, ?, UTC_TIMESTAMP())
        ON DUPLICATE KEY UPDATE status = VALUES(status), confirmed_at = IF(VALUES(status) = 'attending', UTC_TIMESTAMP(), confirmed_at)");
    $statement->execute([(int) $user['id'], $eventId, $status]);
    api_respond(['event_id' => $eventId, 'status' => $status]);
}

function api_attendance_history(): never
{
    $user = api_current_user();
    $statement = api_database()->prepare(api_event_select((int) $user['id']) .
        " INNER JOIN attendance history ON history.event_id = e.id
          WHERE history.user_id = ? AND history.status = 'attending'
          ORDER BY e.start_at DESC");
    $statement->execute([(int) $user['id'], (int) $user['id']]);
    api_respond(array_map('api_event', $statement->fetchAll()));
}

function api_discounts(?int $discountId = null): never
{
    api_current_user(false);
    $category = trim((string) ($_GET['category'] ?? ''));
    $sql = "SELECT d.id, d.store_name, d.title, d.details, d.eligibility, d.claim_instructions,
        DATE_FORMAT(d.valid_from, '%Y-%m-%d') AS valid_from,
        DATE_FORMAT(d.valid_until, '%Y-%m-%d') AS valid_until, c.name AS category
        FROM discounts d INNER JOIN discount_categories c ON c.id = d.category_id
        WHERE d.is_active = 1 AND (d.valid_from IS NULL OR d.valid_from <= CURRENT_DATE)
          AND (d.valid_until IS NULL OR d.valid_until >= CURRENT_DATE)";
    $params = [];
    if ($discountId !== null) {
        $sql .= ' AND d.id = ?';
        $params[] = $discountId;
    } elseif ($category !== '') {
        $sql .= ' AND c.name = ?';
        $params[] = $category;
    }
    $sql .= ' ORDER BY d.store_name';
    $statement = api_database()->prepare($sql);
    $statement->execute($params);
    if ($discountId !== null) {
        $discount = $statement->fetch();
        if (!$discount) {
            api_error(404, 'That discount could not be found.', 'discount_not_found');
        }
        api_respond(api_discount($discount));
    }
    api_respond(array_map('api_discount', $statement->fetchAll()));
}

function api_local_services(): never
{
    api_current_user(false);
    $type = trim((string) ($_GET['type'] ?? ''));
    $sql = 'SELECT id, type, name, address, phone, directions, opening_hours FROM local_services WHERE is_active = 1';
    $params = [];
    if ($type !== '') {
        $sql .= ' AND type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY type, name';
    $statement = api_database()->prepare($sql);
    $statement->execute($params);
    $rows = array_map(static fn (array $row): array => [
        'id' => (int) $row['id'], 'type' => (string) $row['type'], 'name' => (string) $row['name'],
        'address' => (string) $row['address'], 'phone' => (string) $row['phone'],
        'directions' => $row['directions'] === null ? null : (string) $row['directions'],
        'opening_hours' => $row['opening_hours'] === null ? null : (string) $row['opening_hours'],
    ], $statement->fetchAll());
    api_respond($rows);
}

function api_contact(): never
{
    $user = api_current_user(false);
    $input = api_input();
    $name = trim((string) ($input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $message = trim((string) ($input['message'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || mb_strlen($message) < 10 || mb_strlen($message) > 3000) {
        api_error(422, 'Enter your name, a valid email address, and a message between 10 and 3,000 characters.', 'validation_error');
    }
    $statement = api_database()->prepare("INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (?, ?, ?, 'General enquiry', ?)");
    $statement->execute([$user['id'] ?? null, $name, $email, $message]);
    api_respond(['id' => (int) api_database()->lastInsertId(), 'message' => 'Thank you. Your message has been received.'], 201);
}

function api_update_profile(): never
{
    $user = api_current_user();
    $input = api_input();
    $name = trim((string) ($input['full_name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $phone = trim((string) ($input['phone'] ?? '')) ?: null;
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 190 || ($phone !== null && mb_strlen($phone) > 30)) {
        api_error(422, 'Enter a valid name, email address, and phone number.', 'validation_error');
    }
    try {
        api_database()->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?')
            ->execute([$name, $email, $phone, $user['id']]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            api_error(409, 'An account with that email address already exists.', 'email_exists');
        }
        throw $exception;
    }
    $user['full_name'] = $name;
    $user['email'] = $email;
    $user['phone'] = $phone;
    api_respond(api_member($user));
}

function api_update_preferences(): never
{
    $user = api_current_user();
    $input = api_input();
    foreach (['notifications_enabled', 'event_reminders_enabled', 'discount_alerts_enabled'] as $field) {
        if (!array_key_exists($field, $input) || !is_bool($input[$field])) {
            api_error(422, 'All notification preferences must be true or false.', 'validation_error');
        }
    }
    api_database()->prepare('UPDATE users SET notifications_enabled = ?, event_reminders_enabled = ?, discount_alerts_enabled = ? WHERE id = ?')
        ->execute([(int) $input['notifications_enabled'], (int) $input['event_reminders_enabled'], (int) $input['discount_alerts_enabled'], $user['id']]);
    $user = array_merge($user, $input);
    api_respond(api_member($user));
}

function api_register_device(): never
{
    $user = api_current_user();
    $input = api_input();
    $token = trim((string) ($input['fcm_token'] ?? ''));
    $enabled = $input['notifications_enabled'] ?? null;
    if ($token === '' || strlen($token) > 512 || !is_bool($enabled)) {
        api_error(422, 'A valid FCM token and notification preference are required.', 'validation_error');
    }
    api_database()->prepare("INSERT INTO devices (user_id, fcm_token, platform, notifications_enabled, last_seen_at)
        VALUES (?, ?, 'android', ?, UTC_TIMESTAMP())
        ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), notifications_enabled = VALUES(notifications_enabled), last_seen_at = UTC_TIMESTAMP()")
        ->execute([$user['id'], $token, (int) $enabled]);
    api_respond(['registered' => true]);
}

function api_dispatch(): never
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $path = api_path();

    if ($method === 'POST' && $path === '/auth/register') api_register();
    if ($method === 'POST' && $path === '/auth/login') api_login();
    if ($method === 'POST' && $path === '/auth/forgot-password') api_forgot_password();
    if ($method === 'POST' && $path === '/auth/reset-password') api_reset_password();
    if ($method === 'DELETE' && $path === '/auth/session') {
        $user = api_current_user();
        api_database()->prepare('UPDATE auth_sessions SET revoked_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$user['session_id']]);
        api_respond(['logged_out' => true]);
    }
    if ($method === 'GET' && $path === '/me') api_respond(api_member(api_current_user()));
    if ($method === 'PATCH' && $path === '/me') api_update_profile();
    if ($method === 'PATCH' && $path === '/me/notification-preferences') api_update_preferences();
    if ($method === 'GET' && $path === '/me/attendance') api_attendance_history();
    if ($method === 'DELETE' && $path === '/me') {
        $user = api_current_user();
        api_database()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
        api_respond(['deleted' => true]);
    }
    if ($method === 'GET' && $path === '/events') api_events();
    if (preg_match('#^/events/(\d+)/attendance$#', $path, $matches) && $method === 'PUT') api_set_attendance((int) $matches[1]);
    if (preg_match('#^/events/(\d+)$#', $path, $matches) && $method === 'GET') api_event_detail((int) $matches[1]);
    if ($method === 'GET' && $path === '/discounts') api_discounts();
    if (preg_match('#^/discounts/(\d+)$#', $path, $matches) && $method === 'GET') api_discounts((int) $matches[1]);
    if ($method === 'GET' && $path === '/local-services') api_local_services();
    if ($method === 'POST' && $path === '/contact') api_contact();
    if ($method === 'PUT' && $path === '/devices/fcm-token') api_register_device();

    api_error(404, 'The requested API endpoint does not exist.', 'not_found');
}
