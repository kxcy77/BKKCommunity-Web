<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();
$action = (string) ($_POST['action'] ?? '');

if ($action === 'logout') {
    logout_user();
    flash('success', 'You have been logged out safely.');
    redirect_to('index.php');
}

if ($action === 'login') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (rate_limit_blocked('login', 8, 900)) {
        flash('error', 'Too many login attempts. Wait 15 minutes before trying again.');
        redirect_to('login.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        rate_limit_hit('login');
        flash('error', 'Enter a valid email address and password.');
        redirect_to('login.php');
    }
    if (!attempt_login($email, $password)) {
        rate_limit_hit('login');
        flash('error', 'The email address or password was incorrect.');
        redirect_to('login.php');
    }
    rate_limit_clear('login');
    $destination = safe_return_path($_SESSION['return_after_login'] ?? null, is_admin() ? 'admin/index.php' : 'profile.php');
    unset($_SESSION['return_after_login']);
    flash('success', 'Welcome back, ' . (current_user()['full_name'] ?? 'member') . '.');
    redirect_to($destination);
}

if ($action === 'register') {
    if (rate_limit_blocked('register', 5, 3600)) {
        flash('error', 'Too many registration attempts. Wait before trying again.');
        redirect_to('register.php');
    }
    rate_limit_hit('register');
    $input = [
        'full_name' => trim((string) ($_POST['full_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'password' => (string) ($_POST['password'] ?? ''),
    ];
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $passwordIsStrong = strlen($input['password']) >= 10
        && preg_match('/[a-z]/', $input['password'])
        && preg_match('/[A-Z]/', $input['password'])
        && preg_match('/\d/', $input['password']);
    if (mb_strlen($input['full_name']) < 2 || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter your full name and a valid email address.');
        redirect_to('register.php');
    }
    if (!$passwordIsStrong) {
        flash('error', 'Use a password of at least 10 characters with upper- and lowercase letters and a number.');
        redirect_to('register.php');
    }
    if (!hash_equals($input['password'], $confirmation)) {
        flash('error', 'The two passwords did not match.');
        redirect_to('register.php');
    }
    if (($_POST['privacy_consent'] ?? '') !== '1') {
        flash('error', 'Please confirm the privacy notice before creating an account.');
        redirect_to('register.php');
    }
    [$created, $message] = register_user($input);
    flash($created ? 'success' : 'error', $message);
    redirect_to($created ? 'login.php' : 'register.php');
}

if ($action === 'request_reset') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (rate_limit_blocked('password_reset', 5, 3600)) {
        flash('info', 'If an account matches that address, reset instructions will be sent.');
        redirect_to('login.php');
    }
    rate_limit_hit('password_reset');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email address.');
        redirect_to('reset-password.php');
    }
    $db = database();
    $mailFrom = trim((string) app_config('mail_from'));
    if ($db && $mailFrom !== '') {
        $statement = $db->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$email]);
        $userId = $statement->fetchColumn();
        if ($userId) {
            $rawToken = bin2hex(random_bytes(32));
            $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$userId]);
            $insert = $db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
            $insert->execute([$userId, hash('sha256', $rawToken)]);
            $link = rtrim((string) app_config('app_url'), '/') . app_url('new-password.php?token=' . rawurlencode($rawToken));
            $subject = 'Reset your BKK Community password';
            $body = "A password reset was requested for your BKK Community account.\n\nUse this link within one hour:\n{$link}\n\nIf you did not request this, ignore this email.";
            $headers = "From: {$mailFrom}\r\nContent-Type: text/plain; charset=UTF-8";
            if (!mail($email, $subject, $body, $headers)) {
                $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$userId]);
                error_log('BKK password reset email could not be sent.');
            }
        }
    }
    $suffix = is_demo_mode() ? ' Demo mode does not send email.' : ($mailFrom === '' ? ' Email delivery is not configured yet.' : ' Check your inbox and spam folder.');
    flash('info', 'If an account matches that address, reset instructions will be sent.' . $suffix);
    redirect_to('login.php');
}

if ($action === 'complete_reset') {
    $token = trim((string) ($_POST['token'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $passwordIsStrong = strlen($password) >= 10 && preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password) && preg_match('/\d/', $password);
    if (!preg_match('/^[a-f0-9]{64}$/', $token) || !$passwordIsStrong || !hash_equals($password, $confirmation)) {
        flash('error', 'The reset link or new password was invalid. Use at least 10 characters with upper- and lowercase letters and a number.');
        redirect_to('new-password.php?token=' . rawurlencode($token));
    }
    $db = database();
    if (!$db) {
        flash('error', 'Password reset is unavailable in demonstration mode.');
        redirect_to('login.php');
    }
    $statement = $db->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $statement->execute([hash('sha256', $token)]);
    $reset = $statement->fetch();
    if (!$reset) {
        flash('error', 'That reset link is invalid or has expired. Request a new one.');
        redirect_to('reset-password.php');
    }
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([$reset['id']]);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        error_log('BKK password reset failed: ' . $exception->getMessage());
        flash('error', 'The password could not be changed. Please try again.');
        redirect_to('reset-password.php');
    }
    flash('success', 'Your password was changed. You can now log in.');
    redirect_to('login.php');
}

if ($action === 'contact') {
    if (rate_limit_blocked('contact', 5, 600)) {
        flash('error', 'Too many messages were submitted. Wait 10 minutes before trying again.');
        redirect_to('contact.php');
    }
    $input = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'subject' => trim((string) ($_POST['subject'] ?? 'General enquiry')),
        'message' => trim((string) ($_POST['message'] ?? '')),
    ];
    $allowedSubjects = ['General enquiry', 'Event enquiry', 'Discount enquiry', 'Local service enquiry', 'Website support'];
    if (mb_strlen($input['name']) < 2 || !filter_var($input['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($input['phone']) > 30 || !in_array($input['subject'], $allowedSubjects, true) || mb_strlen($input['message']) < 10) {
        flash('error', 'Enter your name, a valid email address and a message of at least 10 characters.');
        redirect_to('contact.php');
    }
    if (mb_strlen($input['message']) > 2000) {
        flash('error', 'Please shorten your message to 2,000 characters or fewer.');
        redirect_to('contact.php');
    }
    rate_limit_hit('contact');
    save_contact_message($input);
    flash('success', is_demo_mode() ? 'Your message was saved for this demo session only.' : 'Your message was received. The community team will reply as soon as possible.');
    redirect_to('contact.php');
}

if ($action === 'toggle_rsvp') {
    $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
    $returnTo = safe_return_path($_POST['return_to'] ?? null, 'events.php');
    $validEventIds = array_map('intval', array_column(all_events(), 'id'));
    if (!$eventId || !in_array($eventId, $validEventIds, true)) {
        flash('error', 'That event could not be found.');
        redirect_to('events.php');
    }
    if (!current_user()) {
        $_SESSION['return_after_login'] = $returnTo;
        flash('info', 'Please log in before confirming attendance.');
        redirect_to('login.php');
    }
    $attending = toggle_rsvp($eventId);
    $message = $attending ? 'Attendance confirmed.' : 'Attendance cancelled.';
    if (is_demo_mode()) {
        $message .= ' This change is stored only for the current demo session.';
    }
    flash('success', $message);
    redirect_to($returnTo);
}

if ($action === 'update_profile') {
    require_login();
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || mb_strlen($phone) > 30) {
        flash('error', 'Enter a valid name and phone number.');
        redirect_to('profile.php');
    }
    $user = current_user();
    if ($db = database()) {
        $db->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?')->execute([$name, $phone, $user['id']]);
    }
    $_SESSION['user']['full_name'] = $name;
    $_SESSION['user']['phone'] = $phone;
    flash('success', is_demo_mode() ? 'Profile updated for this demo session.' : 'Profile updated.');
    redirect_to('profile.php');
}

if ($action === 'update_preferences') {
    require_login();
    $preferences = [
        'event_reminders' => isset($_POST['event_reminders']),
        'discount_alerts' => isset($_POST['discount_alerts']),
    ];
    $_SESSION['notification_preferences'] = $preferences;
    $user = current_user();
    if (($db = database()) && !empty($user['id'])) {
        $db->prepare('UPDATE users SET event_reminders_enabled = ?, discount_alerts_enabled = ? WHERE id = ?')->execute([(int) $preferences['event_reminders'], (int) $preferences['discount_alerts'], $user['id']]);
    }
    $_SESSION['user']['event_reminders'] = $preferences['event_reminders'];
    $_SESSION['user']['discount_alerts'] = $preferences['discount_alerts'];
    flash('success', is_demo_mode() ? 'Preferences saved for this demo session.' : 'Notification preferences saved.');
    redirect_to('profile.php');
}

if ($action === 'delete_account') {
    require_login();
    $user = current_user();
    $confirmation = strtolower(trim((string) ($_POST['confirm_email'] ?? '')));
    if (!hash_equals(strtolower((string) $user['email']), $confirmation)) {
        flash('error', 'The confirmation email did not match your account.');
        redirect_to('profile.php');
    }
    if (($db = database()) && !empty($user['id'])) {
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
    } else {
        unset($_SESSION['demo_registered_users'][strtolower((string) $user['email'])]);
    }
    logout_user();
    flash('success', is_demo_mode() ? 'The demo account was removed from this session.' : 'Your account and attendance records were deleted.');
    redirect_to('index.php');
}

if ($action === 'admin_create_event') {
    require_admin();
    $event = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'date' => trim((string) ($_POST['date'] ?? '')),
        'time' => trim((string) ($_POST['time'] ?? '')),
        'end_time' => trim((string) ($_POST['end_time'] ?? '')),
        'location' => trim((string) ($_POST['location'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? 'Community')),
        'tone' => trim((string) ($_POST['tone'] ?? 'blue')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];
    $startAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $event['date'] . ' ' . $event['time']);
    $endAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $event['date'] . ' ' . $event['end_time']);
    $exactStart = $startAt && $startAt->format('Y-m-d H:i') === $event['date'] . ' ' . $event['time'];
    $exactEnd = $endAt && $endAt->format('Y-m-d H:i') === $event['date'] . ' ' . $event['end_time'];
    if (mb_strlen($event['title']) < 3 || !$exactStart || !$exactEnd || $endAt <= $startAt || $startAt <= new DateTimeImmutable('now') || mb_strlen($event['location']) < 3 || mb_strlen($event['description']) < 10) {
        flash('error', 'Complete every event field with valid information.');
        redirect_to('admin/events.php');
    }
    admin_create_event($event);
    flash('success', 'The event was added' . (is_demo_mode() ? ' for this demo session.' : '.'));
    redirect_to('admin/events.php');
}

if ($action === 'admin_delete_event') {
    require_admin();
    $eventId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$eventId) {
        flash('error', 'That event could not be found.');
        redirect_to('admin/events.php');
    }
    admin_delete_event((int) $eventId);
    flash('success', 'The event was deleted.');
    redirect_to('admin/events.php');
}

if ($action === 'admin_create_discount') {
    require_admin();
    $discount = [
        'store_name' => trim((string) ($_POST['store_name'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'deal' => trim((string) ($_POST['deal'] ?? '')),
        'eligibility' => trim((string) ($_POST['eligibility'] ?? '')),
        'claim_instructions' => trim((string) ($_POST['claim_instructions'] ?? '')),
        'tone' => trim((string) ($_POST['tone'] ?? 'gold')),
    ];
    if (mb_strlen($discount['store_name']) < 2 || mb_strlen($discount['category']) < 2 || mb_strlen($discount['deal']) < 5 || mb_strlen($discount['eligibility']) < 3 || mb_strlen($discount['claim_instructions']) < 5) {
        flash('error', 'Complete every discount field with clear information.');
        redirect_to('admin/discounts.php');
    }
    admin_create_discount($discount);
    flash('success', 'The discount was added' . (is_demo_mode() ? ' for this demo session.' : '.'));
    redirect_to('admin/discounts.php');
}

if ($action === 'admin_delete_discount') {
    require_admin();
    $discountId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$discountId) {
        flash('error', 'That discount could not be found.');
        redirect_to('admin/discounts.php');
    }
    admin_delete_discount((int) $discountId);
    flash('success', 'The discount was deleted.');
    redirect_to('admin/discounts.php');
}

if ($action === 'admin_update_message') {
    require_admin();
    $messageId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = trim((string) ($_POST['status'] ?? ''));
    if (!$messageId || !admin_update_contact_status((int) $messageId, $status)) {
        flash('error', 'The message status could not be updated.');
    } else {
        flash('success', 'The message status was updated.');
    }
    redirect_to('admin/messages.php');
}

if ($action === 'admin_create_service') {
    require_admin();
    $service = [
        'type' => trim((string) ($_POST['type'] ?? '')),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'directions' => trim((string) ($_POST['directions'] ?? '')),
        'opening_hours' => trim((string) ($_POST['opening_hours'] ?? '')),
    ];
    $types = ['pharmacy', 'clinic', 'shop', 'support', 'transport'];
    if (!in_array($service['type'], $types, true) || mb_strlen($service['name']) < 2 || mb_strlen($service['address']) < 4 || mb_strlen($service['phone']) < 5 || mb_strlen($service['phone']) > 30 || mb_strlen($service['directions']) < 5 || mb_strlen($service['opening_hours']) < 3) {
        flash('error', 'Complete every local-service field with valid information.');
        redirect_to('admin/services.php');
    }
    admin_create_service($service);
    flash('success', 'The local service was added' . (is_demo_mode() ? ' for this demo session.' : '.'));
    redirect_to('admin/services.php');
}

if ($action === 'admin_delete_service') {
    require_admin();
    $serviceId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$serviceId) {
        flash('error', 'That local service could not be found.');
        redirect_to('admin/services.php');
    }
    admin_delete_service((int) $serviceId);
    flash('success', 'The local service was deleted.');
    redirect_to('admin/services.php');
}

http_response_code(400);
flash('error', 'The requested action was not recognised.');
redirect_to('index.php');
