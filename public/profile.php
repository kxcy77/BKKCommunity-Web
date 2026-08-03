<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$pageTitle = 'My profile';
$activeNav = '';
$user = current_user();
$rsvpIds = rsvp_ids_for_current_user();
$savedEvents = array_values(array_filter(all_events(), fn (array $event): bool => in_array((int) $event['id'], $rsvpIds, true)));
$attendanceHistory = attendance_history_for_current_user();
$preferences = $_SESSION['notification_preferences'] ?? [
    'event_reminders' => (bool) ($user['event_reminders'] ?? true),
    'discount_alerts' => (bool) ($user['discount_alerts'] ?? true),
];

require __DIR__ . '/partials/header.php';
?>
<section class="section">
    <div class="shell profile-grid">
        <aside class="profile-summary">
            <div class="profile-avatar"><?= icon_svg('user') ?></div>
            <h1><?= h($user['full_name']) ?></h1>
            <p><?= h($user['email']) ?></p>
            <?php if (!empty($user['phone'])): ?><p><?= h($user['phone']) ?></p><?php endif; ?>
            <span class="category-badge">Member account</span>
        </aside>
        <div class="profile-content">
            <section class="panel">
                <h2>My upcoming events</h2>
                <?php if (!$savedEvents): ?>
                    <div class="empty-state"><p>You have not confirmed attendance yet.</p><a class="button" href="<?= h(app_url('events.php')) ?>">Find an event</a></div>
                <?php else: ?>
                    <div class="event-list compact-events">
                        <?php foreach ($savedEvents as $event): ?>
                            <article class="event-card tone-<?= h($event['tone']) ?>">
                                <div class="event-date"><span><?= h(date('D', strtotime($event['date']))) ?></span><strong><?= h(date('d', strtotime($event['date']))) ?></strong><span><?= h(date('M', strtotime($event['date']))) ?></span></div>
                                <div class="event-content"><h3><?= h($event['title']) ?></h3><div class="event-meta"><span><?= icon_svg('clock') ?><?= h($event['time']) ?></span><span><?= icon_svg('pin') ?><?= h($event['location']) ?></span></div></div>
                                <div class="event-action"><form action="<?= h(app_url('actions.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle_rsvp"><input type="hidden" name="event_id" value="<?= h($event['id']) ?>"><input type="hidden" name="return_to" value="profile.php"><button class="button button-outline" type="submit">Cancel attendance</button></form></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            <section class="panel">
                <h2>Attendance history</h2>
                <?php if (!$attendanceHistory): ?>
                    <div class="empty-state"><p>Your attendance history will appear here after you confirm an event.</p></div>
                <?php else: ?>
                    <div class="table-wrap" role="region" aria-label="Attendance history table" tabindex="0"><table class="data-table"><thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Status</th></tr></thead><tbody>
                        <?php foreach ($attendanceHistory as $attendance): ?><tr><td><strong><?= h($attendance['title']) ?></strong><br><small><?= h($attendance['category']) ?></small></td><td><?= h((new DateTimeImmutable($attendance['date']))->format('d M Y')) ?> at <?= h($attendance['time']) ?></td><td><?= h($attendance['location']) ?></td><td><?= ($attendance['status'] ?? 'attending') === 'attending' ? 'Attending' : 'Cancelled' ?></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>
            <section class="panel">
                <h2>Contact details</h2>
                <form action="<?= h(app_url('actions.php')) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="update_profile">
                    <div class="form-grid"><div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required value="<?= h($user['full_name']) ?>"></div><div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" value="<?= h($user['phone'] ?? '') ?>"></div></div>
                    <button class="button" type="submit">Save details</button>
                </form>
            </section>
            <section class="panel">
                <h2>Notification preferences</h2>
                <form action="<?= h(app_url('actions.php')) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="update_preferences">
                    <div class="preference-list">
                        <label><input type="checkbox" name="event_reminders" value="1" <?= !empty($preferences['event_reminders']) ? 'checked' : '' ?>> Event reminders 24 hours before</label>
                        <label><input type="checkbox" name="discount_alerts" value="1" <?= !empty($preferences['discount_alerts']) ? 'checked' : '' ?>> New senior-discount alerts</label>
                    </div>
                    <button class="button" type="submit">Save preferences</button>
                </form>
            </section>
            <section class="panel danger-panel">
                <h2>Delete my account</h2>
                <p>This permanently removes the account and attendance records from a configured database. Enter your email to confirm.</p>
                <form action="<?= h(app_url('actions.php')) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete_account">
                    <div class="field"><label for="confirm_email">Confirm email address</label><input id="confirm_email" name="confirm_email" type="email" required></div>
                    <button class="button button-danger" type="submit" data-confirm="Delete this account permanently?">Delete my account</button>
                </form>
            </section>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
