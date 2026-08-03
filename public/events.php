<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Community events';
$activeNav = 'events';
$category = trim((string) ($_GET['category'] ?? 'All'));
$events = all_events();
$categories = array_values(array_unique(array_column($events, 'category')));
if ($category !== 'All') {
    $events = array_values(array_filter($events, fn (array $event): bool => strcasecmp($event['category'], $category) === 0));
}
$rsvpIds = rsvp_ids_for_current_user();
$calendarEvents = all_events();
require __DIR__ . '/partials/header.php';
?>

<section class="page-hero">
    <div class="shell">
        <p class="eyebrow eyebrow-light">Stay active together</p>
        <h1>Community events</h1>
        <p>Use the simple calendar for a quick overview, or read the detailed list below. Browsing is open to everyone.</p>
    </div>
</section>

<section class="section">
    <div class="shell">
        <nav class="filter-bar" aria-label="Filter events by category">
            <a class="filter-pill" href="<?= h(app_url('events.php')) ?>" aria-current="<?= $category === 'All' ? 'true' : 'false' ?>">All events</a>
            <?php foreach ($categories as $item): ?>
                <a class="filter-pill" href="<?= h(app_url('events.php?category=' . urlencode($item))) ?>" aria-current="<?= $category === $item ? 'true' : 'false' ?>"><?= h($item) ?></a>
            <?php endforeach; ?>
        </nav>

        <section class="calendar-panel" aria-labelledby="calendar-heading">
            <div class="calendar-header"><h2 id="calendar-heading">August 2026</h2><span>Event days are marked with an “E”</span></div>
            <div class="calendar-scroll" tabindex="0" aria-label="Scrollable August 2026 event calendar"><div class="calendar-grid">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $weekday): ?><div class="calendar-weekday"><?= h($weekday) ?></div><?php endforeach; ?>
                <?php for ($blank = 0; $blank < 6; $blank++): ?><div class="calendar-day"></div><?php endfor; ?>
                <?php for ($day = 1; $day <= 31; $day++): ?>
                    <?php $hasEvent = array_filter($calendarEvents, fn (array $event): bool => (int) (new DateTimeImmutable($event['date']))->format('j') === $day); ?>
                    <div class="calendar-day <?= $hasEvent ? 'has-event' : '' ?>">
                        <span><?= $day ?></span><?php if ($hasEvent): ?><span class="calendar-marker" aria-hidden="true">E</span><span class="visually-hidden">Event scheduled</span><?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div></div>
        </section>

        <div class="section-heading">
            <div><p class="eyebrow">Detailed list</p><h2><?= $category === 'All' ? 'All upcoming events' : h($category) . ' events' ?></h2></div>
        </div>
        <?php if (!$events): ?>
            <div class="empty-state"><h2>No events in this category</h2><p>Choose another category or return to all events.</p></div>
        <?php else: ?>
            <div class="event-list">
                <?php foreach ($events as $event): ?>
                    <?php $date = new DateTimeImmutable($event['date']); $attending = in_array((int) $event['id'], $rsvpIds, true); ?>
                    <article class="event-card tone-<?= h($event['tone']) ?>">
                        <div class="event-date"><span><?= h($date->format('M')) ?></span><strong><?= h($date->format('d')) ?></strong><span><?= h($date->format('D')) ?></span></div>
                        <div class="event-content">
                            <h3><?= h($event['title']) ?></h3>
                            <div class="event-meta"><span><?= icon_svg('clock') ?> <?= h($event['time']) ?>–<?= h($event['end_time']) ?></span><span><?= icon_svg('pin') ?> <?= h($event['location']) ?></span><span><?= h($event['category']) ?></span></div>
                            <p><?= h($event['description']) ?></p>
                        </div>
                        <div class="event-action">
                            <form action="<?= h(app_url('actions.php')) ?>" method="post">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle_rsvp"><input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>"><input type="hidden" name="return_to" value="events.php<?= $category !== 'All' ? '?category=' . urlencode($category) : '' ?>">
                                <button class="button <?= $attending ? 'rsvp-active' : '' ?>" type="submit"><?= $attending ? icon_svg('check') . ' Attending' : 'I will attend' ?></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
