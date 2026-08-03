<aside class="admin-sidebar">
    <h2>BKK Admin</h2>
    <nav class="admin-nav" aria-label="Administrator navigation" tabindex="0">
        <a href="<?= h(app_url('admin/index.php')) ?>" <?= ($adminPage ?? '') === 'dashboard' ? 'aria-current="page"' : '' ?>><?= icon_svg('shield') ?> Dashboard</a>
        <a href="<?= h(app_url('admin/events.php')) ?>" <?= ($adminPage ?? '') === 'events' ? 'aria-current="page"' : '' ?>><?= icon_svg('calendar') ?> Manage events</a>
        <a href="<?= h(app_url('admin/discounts.php')) ?>" <?= ($adminPage ?? '') === 'discounts' ? 'aria-current="page"' : '' ?>><?= icon_svg('tag') ?> Manage discounts</a>
        <a href="<?= h(app_url('admin/services.php')) ?>" <?= ($adminPage ?? '') === 'services' ? 'aria-current="page"' : '' ?>><?= icon_svg('pin') ?> Manage local services</a>
        <a href="<?= h(app_url('admin/messages.php')) ?>" <?= ($adminPage ?? '') === 'messages' ? 'aria-current="page"' : '' ?>><?= icon_svg('mail') ?> Contact messages</a>
    </nav>
</aside>
