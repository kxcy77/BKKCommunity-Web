<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pageTitle = 'Manage local services';
$bodyClass = 'admin-page';
$adminPage = 'services';
$services = admin_all_services();
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <header class="admin-header"><div><p class="eyebrow">Content management</p><h1>Manage local services</h1></div><a class="button button-outline" href="<?= h(app_url('info.php')) ?>">View public page</a></header>
        <form class="panel" action="<?= h(app_url('actions.php')) ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="admin_create_service">
            <h2>Add a local service</h2>
            <div class="form-grid">
                <div class="field"><label for="type">Service type</label><select id="type" name="type" required><option value="clinic">Clinic</option><option value="pharmacy">Pharmacy</option><option value="shop">Shop</option><option value="support">Support</option><option value="transport">Transport</option></select></div>
                <div class="field"><label for="name">Service name</label><input id="name" name="name" required minlength="2"></div>
                <div class="field field-full"><label for="address">Address</label><input id="address" name="address" required minlength="4"></div>
                <div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" required minlength="5" maxlength="30"></div>
                <div class="field"><label for="opening_hours">Opening hours</label><input id="opening_hours" name="opening_hours" required placeholder="Mon–Fri, 08:00–17:00"></div>
                <div class="field field-full"><label for="directions">Directions and support information</label><textarea id="directions" name="directions" required minlength="5"></textarea></div>
            </div>
            <button class="button" type="submit">Add local service</button>
        </form>
        <section class="panel admin-list-panel">
            <h2>Current services</h2>
            <div class="table-wrap" role="region" aria-label="Current local services table" tabindex="0"><table class="data-table"><thead><tr><th>Service</th><th>Type</th><th>Contact</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($services as $service): ?><tr><td><strong><?= h($service['name']) ?></strong><br><small><?= h($service['address']) ?></small></td><td><?= h(ucwords($service['type'] ?? $service['category'])) ?></td><td><?= h($service['phone']) ?><br><small><?= h($service['opening_hours'] ?? $service['hours']) ?></small></td><td><form action="<?= h(app_url('actions.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="admin_delete_service"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><button class="button button-small button-danger" type="submit" data-confirm="Delete this local service?">Delete</button></form></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </section>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
