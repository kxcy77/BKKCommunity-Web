<?php

declare(strict_types=1);

function database(): ?PDO
{
    static $connection = false;
    global $config;

    if ($connection instanceof PDO) {
        return $connection;
    }
    if ($connection === null) {
        return null;
    }

    $db = $config['db'];
    if (empty($db['host'])) {
        $connection = null;
        return null;
    }

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
        $connection = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('BKK database connection failed: ' . $exception->getMessage());
        http_response_code(503);
        exit('The BKK Community service is temporarily unavailable. No information was saved. Please try again later.');
    }

    return $connection;
}

function tone_from_colour(?string $colour): string
{
    return match (strtoupper((string) $colour)) {
        '#315C24', '#315D23' => 'green',
        '#B00020', '#B4232C' => 'red',
        '#BF7600' => 'gold',
        '#007C83' => 'teal',
        default => 'blue',
    };
}

function tone_for_discount(string $category): string
{
    return match (strtolower($category)) {
        'pharmacy' => 'blue',
        'grocery' => 'green',
        'restaurant' => 'red',
        'transport' => 'gold',
        default => 'teal',
    };
}

function tone_for_service(string $type): string
{
    return match (strtolower($type)) {
        'clinic' => 'red',
        'pharmacy' => 'blue',
        'transport' => 'green',
        'support' => 'teal',
        default => 'gold',
    };
}

function all_events(): array
{
    $db = database();
    if (!$db) {
        $events = $_SESSION['demo_events'] ?? demo_events();
        usort($events, fn (array $a, array $b): int => strcmp($a['date'] . $a['time'], $b['date'] . $b['time']));
        return $events;
    }

    $rows = $db->query("SELECT e.id, e.title,
        DATE_FORMAT(CONVERT_TZ(e.start_at, '+00:00', '+02:00'), '%Y-%m-%d') AS date,
        DATE_FORMAT(CONVERT_TZ(e.start_at, '+00:00', '+02:00'), '%H:%i') AS time,
        DATE_FORMAT(CONVERT_TZ(e.end_at, '+00:00', '+02:00'), '%H:%i') AS end_time,
        e.location, c.name AS category, c.colour_hex, e.description
        FROM events e
        INNER JOIN event_categories c ON c.id = e.category_id
        WHERE e.status = 'published' AND e.end_at >= UTC_TIMESTAMP()
        ORDER BY e.start_at")->fetchAll();
    return array_map(function (array $event): array {
        $event['tone'] = tone_from_colour($event['colour_hex'] ?? null);
        unset($event['colour_hex']);
        return $event;
    }, $rows);
}

function all_discounts(): array
{
    $db = database();
    if (!$db) {
        return $_SESSION['demo_discounts'] ?? demo_discounts();
    }

    $rows = $db->query("SELECT d.id, d.store_name, c.name AS category,
        CONCAT(d.title, ': ', d.details) AS deal,
        d.eligibility, d.claim_instructions
        FROM discounts d
        INNER JOIN discount_categories c ON c.id = d.category_id
        WHERE d.is_active = 1
          AND (d.valid_from IS NULL OR d.valid_from <= CURRENT_DATE)
          AND (d.valid_until IS NULL OR d.valid_until >= CURRENT_DATE)
        ORDER BY d.store_name")->fetchAll();
    return array_map(function (array $discount): array {
        $discount['tone'] = tone_for_discount($discount['category']);
        return $discount;
    }, $rows);
}

function all_services(): array
{
    $db = database();
    if (!$db) {
        return $_SESSION['demo_services'] ?? demo_services();
    }

    $rows = $db->query("SELECT id, type, name, phone, address,
        COALESCE(opening_hours, 'Contact the service for opening times') AS hours,
        COALESCE(directions, 'Contact the service for directions and support information.') AS description
        FROM local_services WHERE is_active = 1 ORDER BY type, name")->fetchAll();
    return array_map(function (array $service): array {
        $service['category'] = ucwords(str_replace('_', ' ', $service['type']));
        $service['tone'] = tone_for_service($service['type']);
        unset($service['type']);
        return $service;
    }, $rows);
}

function rsvp_ids_for_current_user(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    $db = database();
    if (!$db || empty($user['id'])) {
        return array_map('intval', $_SESSION['rsvps'] ?? []);
    }

    $statement = $db->prepare("SELECT event_id FROM attendance WHERE user_id = ? AND status = 'attending'");
    $statement->execute([$user['id']]);
    return array_map('intval', array_column($statement->fetchAll(), 'event_id'));
}

function attendance_history_for_current_user(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    $db = database();
    if (!$db || empty($user['id'])) {
        $ids = rsvp_ids_for_current_user();
        return array_values(array_filter(all_events(), fn (array $event): bool => in_array((int) $event['id'], $ids, true)));
    }

    $statement = $db->prepare("SELECT e.id, e.title, e.location, c.name AS category, a.status,
        DATE_FORMAT(CONVERT_TZ(e.start_at, '+00:00', '+02:00'), '%Y-%m-%d') AS date,
        DATE_FORMAT(CONVERT_TZ(e.start_at, '+00:00', '+02:00'), '%H:%i') AS time
        FROM attendance a
        INNER JOIN events e ON e.id = a.event_id
        INNER JOIN event_categories c ON c.id = e.category_id
        WHERE a.user_id = ?
        ORDER BY e.start_at DESC");
    $statement->execute([$user['id']]);
    return $statement->fetchAll();
}

function toggle_rsvp(int $eventId): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    $db = database();
    if (!$db || empty($user['id'])) {
        $ids = rsvp_ids_for_current_user();
        if (in_array($eventId, $ids, true)) {
            $_SESSION['rsvps'] = array_values(array_diff($ids, [$eventId]));
            return false;
        }
        $ids[] = $eventId;
        $_SESSION['rsvps'] = array_values(array_unique($ids));
        return true;
    }

    $check = $db->prepare('SELECT id, status FROM attendance WHERE user_id = ? AND event_id = ?');
    $check->execute([$user['id'], $eventId]);
    $existing = $check->fetch();
    if ($existing) {
        $attending = $existing['status'] !== 'attending';
        $update = $db->prepare("UPDATE attendance SET status = ?, confirmed_at = IF(? = 'attending', NOW(), confirmed_at) WHERE id = ?");
        $status = $attending ? 'attending' : 'cancelled';
        $update->execute([$status, $status, $existing['id']]);
        return $attending;
    }

    $insert = $db->prepare('INSERT INTO attendance (user_id, event_id) VALUES (?, ?)');
    $insert->execute([$user['id'], $eventId]);
    return true;
}

function save_contact_message(array $input): void
{
    $db = database();
    if (!$db) {
        $_SESSION['demo_contact_messages'][] = $input + ['submitted_at' => date(DATE_ATOM)];
        return;
    }

    $userId = current_user()['id'] ?? null;
    $statement = $db->prepare('INSERT INTO contact_messages (user_id, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([$userId, $input['name'], $input['email'], $input['phone'], $input['subject'], $input['message']]);
}

function admin_metrics(): array
{
    $db = database();
    if (!$db) {
        return [
            'events' => count(all_events()),
            'discounts' => count(all_discounts()),
            'messages' => count($_SESSION['demo_contact_messages'] ?? []),
            'users' => 2 + count($_SESSION['demo_registered_users'] ?? []),
        ];
    }
    return [
        'events' => (int) $db->query("SELECT COUNT(*) FROM events WHERE status = 'published' AND end_at >= UTC_TIMESTAMP()")->fetchColumn(),
        'discounts' => (int) $db->query('SELECT COUNT(*) FROM discounts WHERE is_active = 1')->fetchColumn(),
        'messages' => (int) $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
        'users' => (int) $db->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn(),
    ];
}

function admin_create_event(array $event): void
{
    $db = database();
    if (!$db) {
        $events = $_SESSION['demo_events'] ?? demo_events();
        $event['id'] = $events ? max(array_map('intval', array_column($events, 'id'))) + 1 : 1;
        $events[] = $event;
        $_SESSION['demo_events'] = $events;
        return;
    }
    $colour = match ($event['tone']) {
        'green' => '#315C24', 'teal' => '#007C83', 'red' => '#B00020', 'gold' => '#BF7600', default => '#2E75B6',
    };
    $category = $db->prepare('SELECT id FROM event_categories WHERE name = ? LIMIT 1');
    $category->execute([$event['category']]);
    $categoryId = $category->fetchColumn();
    if (!$categoryId) {
        $db->prepare('INSERT INTO event_categories (name, colour_hex) VALUES (?, ?)')->execute([$event['category'], $colour]);
        $categoryId = (int) $db->lastInsertId();
    }
    $localZone = new DateTimeZone('Africa/Johannesburg');
    $utcZone = new DateTimeZone('UTC');
    $start = new DateTimeImmutable($event['date'] . ' ' . $event['time'], $localZone);
    $end = new DateTimeImmutable($event['date'] . ' ' . $event['end_time'], $localZone);
    $statement = $db->prepare("INSERT INTO events (category_id, title, description, start_at, end_at, location, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
    $statement->execute([$categoryId, $event['title'], $event['description'], $start->setTimezone($utcZone)->format('Y-m-d H:i:s'), $end->setTimezone($utcZone)->format('Y-m-d H:i:s'), $event['location']]);
}

function admin_delete_event(int $eventId): void
{
    $db = database();
    if (!$db) {
        $events = $_SESSION['demo_events'] ?? demo_events();
        $_SESSION['demo_events'] = array_values(array_filter($events, fn (array $event): bool => (int) $event['id'] !== $eventId));
        return;
    }
    $db->prepare('DELETE FROM events WHERE id = ?')->execute([$eventId]);
}

function admin_create_discount(array $discount): void
{
    $db = database();
    if (!$db) {
        $discounts = $_SESSION['demo_discounts'] ?? demo_discounts();
        $discount['id'] = $discounts ? max(array_map('intval', array_column($discounts, 'id'))) + 1 : 1;
        $discounts[] = $discount;
        $_SESSION['demo_discounts'] = $discounts;
        return;
    }
    $category = $db->prepare('SELECT id FROM discount_categories WHERE name = ? LIMIT 1');
    $category->execute([$discount['category']]);
    $categoryId = $category->fetchColumn();
    if (!$categoryId) {
        $db->prepare('INSERT INTO discount_categories (name) VALUES (?)')->execute([$discount['category']]);
        $categoryId = (int) $db->lastInsertId();
    }
    $title = mb_substr($discount['deal'], 0, 190);
    $statement = $db->prepare('INSERT INTO discounts (category_id, store_name, title, details, eligibility, claim_instructions, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    $statement->execute([$categoryId, $discount['store_name'], $title, $discount['deal'], $discount['eligibility'], $discount['claim_instructions']]);
}

function admin_delete_discount(int $discountId): void
{
    $db = database();
    if (!$db) {
        $discounts = $_SESSION['demo_discounts'] ?? demo_discounts();
        $_SESSION['demo_discounts'] = array_values(array_filter($discounts, fn (array $discount): bool => (int) $discount['id'] !== $discountId));
        return;
    }
    $db->prepare('DELETE FROM discounts WHERE id = ?')->execute([$discountId]);
}

function admin_contact_messages(): array
{
    $db = database();
    if (!$db) {
        $messages = $_SESSION['demo_contact_messages'] ?? [];
        return array_reverse(array_map(static function (array $message, int $index): array {
            return $message + ['id' => $index + 1, 'status' => 'new'];
        }, $messages, array_keys($messages)));
    }

    return $db->query('SELECT id, name, email, phone, subject, message, status, submitted_at FROM contact_messages ORDER BY submitted_at DESC')->fetchAll();
}

function admin_update_contact_status(int $messageId, string $status): bool
{
    if (!in_array($status, ['new', 'read', 'resolved'], true)) {
        return false;
    }

    $db = database();
    if (!$db) {
        $index = $messageId - 1;
        if (!isset($_SESSION['demo_contact_messages'][$index])) {
            return false;
        }
        $_SESSION['demo_contact_messages'][$index]['status'] = $status;
        return true;
    }

    $statement = $db->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
    $statement->execute([$status, $messageId]);
    $check = $db->prepare('SELECT COUNT(*) FROM contact_messages WHERE id = ?');
    $check->execute([$messageId]);
    return (int) $check->fetchColumn() === 1;
}

function admin_all_services(): array
{
    $db = database();
    if (!$db) {
        return $_SESSION['demo_services'] ?? demo_services();
    }

    return $db->query("SELECT id, type, name, address, phone, directions, opening_hours, is_active FROM local_services ORDER BY is_active DESC, type, name")->fetchAll();
}

function admin_create_service(array $service): void
{
    $db = database();
    if (!$db) {
        $services = $_SESSION['demo_services'] ?? demo_services();
        $service['id'] = $services ? max(array_map('intval', array_column($services, 'id'))) + 1 : 1;
        $service['category'] = ucwords($service['type']);
        $service['description'] = $service['directions'];
        $service['hours'] = $service['opening_hours'];
        $service['tone'] = tone_for_service($service['type']);
        $services[] = $service;
        $_SESSION['demo_services'] = $services;
        return;
    }

    $statement = $db->prepare('INSERT INTO local_services (type, name, address, phone, directions, opening_hours, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    $statement->execute([$service['type'], $service['name'], $service['address'], $service['phone'], $service['directions'], $service['opening_hours']]);
}

function admin_delete_service(int $serviceId): void
{
    $db = database();
    if (!$db) {
        $services = $_SESSION['demo_services'] ?? demo_services();
        $_SESSION['demo_services'] = array_values(array_filter($services, fn (array $service): bool => (int) $service['id'] !== $serviceId));
        return;
    }

    $db->prepare('DELETE FROM local_services WHERE id = ?')->execute([$serviceId]);
}
