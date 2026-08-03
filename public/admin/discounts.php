<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'Manage discounts';
$bodyClass = 'admin-page';
$adminPage = 'discounts';
$discounts = all_discounts();
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <header class="admin-header"><div><p class="eyebrow">Content management</p><h1>Manage discounts</h1></div><a class="button button-outline" href="<?= h(app_url('discounts.php')) ?>">View public page</a></header>
        <form class="panel" action="<?= h(app_url('actions.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="admin_create_discount"><h2>Add a discount</h2><div class="form-grid"><div class="field"><label for="store_name">Business name</label><input id="store_name" name="store_name" required></div><div class="field"><label for="category">Category</label><select id="category" name="category"><option>Pharmacy</option><option>Grocery</option><option>Restaurant</option><option>Transport</option></select></div><div class="field field-full"><label for="deal">Offer description</label><textarea id="deal" name="deal" required minlength="5"></textarea></div><div class="field"><label for="eligibility">Who qualifies</label><input id="eligibility" name="eligibility" required></div><div class="field"><label for="claim_instructions">How to claim</label><input id="claim_instructions" name="claim_instructions" required></div><div class="field"><label for="tone">Card colour</label><select id="tone" name="tone"><option value="gold">Gold</option><option value="blue">Blue</option><option value="green">Green</option><option value="teal">Teal</option><option value="red">Red</option></select></div></div><button class="button" type="submit">Add discount</button></form>
        <section class="panel admin-list-panel"><h2>Current discounts</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Business</th><th>Category</th><th>Offer</th><th>Action</th></tr></thead><tbody><?php foreach ($discounts as $discount): ?><tr><td><strong><?= h($discount['store_name']) ?></strong></td><td><?= h($discount['category']) ?></td><td><?= h($discount['deal']) ?></td><td><form action="<?= h(app_url('actions.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="admin_delete_discount"><input type="hidden" name="id" value="<?= (int) $discount['id'] ?>"><button class="button button-small button-danger" type="submit" data-confirm="Delete this discount?">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
