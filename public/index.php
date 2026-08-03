<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Stay connected';
$activeNav = 'home';
$events = array_slice(all_events(), 0, 3);
$discounts = array_slice(all_discounts(), 0, 4);
$rsvpIds = rsvp_ids_for_current_user();
require __DIR__ . '/partials/header.php';
?>

<section class="hero">
    <div class="shell hero-grid">
        <div class="hero-copy">
            <p class="eyebrow eyebrow-light">Welcome to BKK Community Group</p>
            <h1>Stay connected.<br><span>Stay active.</span><br>Stay informed.</h1>
            <p>Your trusted community hub for local events, senior savings and practical support—all designed to be clear and easy to use.</p>
            <div class="hero-actions">
                <a class="button button-light" href="<?= h(app_url('events.php')) ?>">View upcoming events <?= icon_svg('arrow') ?></a>
                <a class="button button-outline" href="<?= h(app_url('discounts.php')) ?>">Explore senior discounts</a>
            </div>
        </div>
        <div class="community-visual" aria-hidden="true">
            <div class="community-ring">
                <div class="visual-card">
                    <span class="visual-icon"><?= icon_svg('heart') ?></span>
                    <strong>Community first</strong>
                    <span>Support, connection and useful information in one place.</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-strip" aria-label="Community highlights">
    <div class="shell stats-grid">
        <div class="stat"><strong>200+</strong><span>Community members</span></div>
        <div class="stat"><strong>50+</strong><span>Monthly activities</span></div>
        <div class="stat"><strong>30+</strong><span>Senior savings</span></div>
        <div class="stat"><strong>5</strong><span>Local support areas</span></div>
    </div>
</section>

<section class="section section-white">
    <div class="shell">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Everything in one place</p>
                <h2>What can we help you find?</h2>
                <p>Three simple starting points, with clear information and no unnecessary steps.</p>
            </div>
        </div>
        <div class="feature-grid">
            <a class="feature-card tone-blue" href="<?= h(app_url('events.php')) ?>">
                <span class="feature-icon"><?= icon_svg('calendar') ?></span>
                <h3>Community events</h3>
                <p>See meetings, wellness sessions, social lunches and practical talks. Members can confirm attendance in one step.</p>
                <span class="card-link">Browse events <?= icon_svg('arrow') ?></span>
            </a>
            <a class="feature-card tone-green" href="<?= h(app_url('discounts.php')) ?>">
                <span class="feature-icon"><?= icon_svg('tag') ?></span>
                <h3>Senior discounts</h3>
                <p>Find possible savings at pharmacies, grocery stores, restaurants and transport services.</p>
                <span class="card-link">Find savings <?= icon_svg('arrow') ?></span>
            </a>
            <a class="feature-card tone-teal" href="<?= h(app_url('info.php')) ?>">
                <span class="feature-icon"><?= icon_svg('pin') ?></span>
                <h3>Local information</h3>
                <p>Keep useful phone numbers, addresses, service hours and community support details close at hand.</p>
                <span class="card-link">View local support <?= icon_svg('arrow') ?></span>
            </a>
        </div>
    </div>
</section>

<section class="section section-blue" id="upcoming-events">
    <div class="shell">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Plan your week</p>
                <h2>Upcoming events</h2>
                <p>Confirming attendance helps the group prepare seating, refreshments and support.</p>
            </div>
            <a class="section-link" href="<?= h(app_url('events.php')) ?>">View all events <?= icon_svg('arrow') ?></a>
        </div>
        <div class="event-list">
            <?php foreach ($events as $event): ?>
                <?php $date = new DateTimeImmutable($event['date']); $attending = in_array((int) $event['id'], $rsvpIds, true); ?>
                <article class="event-card tone-<?= h($event['tone']) ?>">
                    <div class="event-date"><span><?= h($date->format('M')) ?></span><strong><?= h($date->format('d')) ?></strong><span><?= h($date->format('D')) ?></span></div>
                    <div class="event-content">
                        <h3><?= h($event['title']) ?></h3>
                        <div class="event-meta">
                            <span><?= icon_svg('clock') ?> <?= h($event['time']) ?>–<?= h($event['end_time']) ?></span>
                            <span><?= icon_svg('pin') ?> <?= h($event['location']) ?></span>
                        </div>
                    </div>
                    <div class="event-action">
                        <form action="<?= h(app_url('actions.php')) ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle_rsvp">
                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                            <input type="hidden" name="return_to" value="index.php">
                            <button class="button <?= $attending ? 'rsvp-active' : '' ?>" type="submit"><?= $attending ? icon_svg('check') . ' Attending' : 'I will attend' ?></button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-gold">
    <div class="shell">
        <div class="section-heading">
            <div>
                <p class="eyebrow eyebrow-gold">Featured savings</p>
                <h2>Spend a little less</h2>
                <p>Offers can change. Always confirm availability and eligibility with the participating business before purchasing.</p>
            </div>
            <a class="section-link" href="<?= h(app_url('discounts.php')) ?>">See all discounts <?= icon_svg('arrow') ?></a>
        </div>
        <div class="discount-row">
            <?php foreach ($discounts as $discount): ?>
                <article class="discount-mini"><strong><?= h($discount['store_name']) ?></strong><span><?= h($discount['deal']) ?></span></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
