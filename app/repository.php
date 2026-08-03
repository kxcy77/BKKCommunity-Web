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

function all_events(): array
{
    $db = database();
    if (!$db) {
        $events = $_SESSION['demo_events'] ?? demo_events();
        usort($events, fn (array $a, array $b): int => strcmp($a['date'] . $a['time'], $b['date'] . $b['time']));
        return $events;
    }

    return $db->query("SELECT id, title, event_date AS date, event_time AS time, end_time, location, category, tone, description FROM events WHERE event_date >= CURRENT_DATE ORDER BY event_date, event_time")->fetchAll();
}

function all_discounts(): array
{
    $db = database();
    if (!$db) {
        return $_SESSION['demo_discounts'] ?? demo_discounts();
    }

    return $db->query("SELECT id, store_name, category, deal, eligibility, claim_instructions, tone FROM discounts WHERE active = 1 ORDER BY store_name")->fetchAll();
}

function all_services(): array
{
    $db = database();
    if (!$db) {
        return demo_services();
    }

    return $db->query("SELECT id, name, category, phone, address, hours, description, tone FROM local_services WHERE active = 1 ORDER BY category, name")->fetchAll();
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

    $statement = $db->prepare('SELECT event_id FROM attendance WHERE user_id = ?');
    $statement->execute([$user['id']]);
    return array_map('intval', array_column($statement->fetchAll(), 'event_id'));
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

    $check = $db->prepare('SELECT id FROM attendance WHERE user_id = ? AND event_id = ?');
    $check->execute([$user['id'], $eventId]);
    $existing = $check->fetchColumn();
    if ($existing) {
        $delete = $db->prepare('DELETE FROM attendance WHERE id = ?');
        $delete->execute([$existing]);
        return false;
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

    $statement = $db->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$input['name'], $input['email'], $input['phone'], $input['subject'], $input['message']]);
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
        'events' => (int) $db->query('SELECT COUNT(*) FROM events WHERE event_date >= CURRENT_DATE')->fetchColumn(),
        'discounts' => (int) $db->query('SELECT COUNT(*) FROM discounts WHERE active = 1')->fetchColumn(),
        'messages' => (int) $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
        'users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
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
    $statement = $db->prepare('INSERT INTO events (title, event_date, event_time, end_time, location, category, tone, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $statement->execute([$event['title'], $event['date'], $event['time'], $event['end_time'], $event['location'], $event['category'], $event['tone'], $event['description']]);
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
    $statement = $db->prepare('INSERT INTO discounts (store_name, category, deal, eligibility, claim_instructions, tone, active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    $statement->execute([$discount['store_name'], $discount['category'], $discount['deal'], $discount['eligibility'], $discount['claim_instructions'], $discount['tone']]);
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
