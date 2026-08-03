<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'Manage events';
$bodyClass = 'admin-page';
$adminPage = 'events';
$events = all_events();
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <header class="admin-header"><div><p class="eyebrow">Content management</p><h1>Manage events</h1></div><a class="button button-outline" href="<?= h(app_url('events.php')) ?>">View public page</a></header>
        <form class="panel" action="<?= h(app_url('actions.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="admin_create_event"><h2>Add an event</h2><div class="form-grid"><div class="field field-full"><label for="title">Event title</label><input id="title" name="title" required minlength="3"></div><div class="field"><label for="date">Date</label><input id="date" name="date" type="date" required></div><div class="field"><label for="category">Category</label><select id="category" name="category"><option>Community</option><option>Wellness</option><option>Social</option><option>Health</option><option>Support</option></select></div><div class="field"><label for="time">Start time</label><input id="time" name="time" type="time" required></div><div class="field"><label for="end_time">End time</label><input id="end_time" name="end_time" type="time" required></div><div class="field field-full"><label for="location">Location</label><input id="location" name="location" required minlength="3"></div><div class="field"><label for="tone">Card colour</label><select id="tone" name="tone"><option value="blue">Blue</option><option value="green">Green</option><option value="teal">Teal</option><option value="red">Red</option><option value="gold">Gold</option></select></div><div class="field field-full"><label for="description">Description</label><textarea id="description" name="description" required minlength="10"></textarea></div></div><button class="button" type="submit">Add event</button></form>
        <section class="panel admin-list-panel"><h2>Current events</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Action</th></tr></thead><tbody><?php foreach ($events as $event): ?><tr><td><strong><?= h($event['title']) ?></strong><br><small><?= h($event['category']) ?></small></td><td><?= h((new DateTimeImmutable($event['date']))->format('d M Y')) ?></td><td><?= h($event['location']) ?></td><td><form action="<?= h(app_url('actions.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="admin_delete_event"><input type="hidden" name="id" value="<?= (int) $event['id'] ?>"><button class="button button-small button-danger" type="submit" data-confirm="Delete this event?">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>

