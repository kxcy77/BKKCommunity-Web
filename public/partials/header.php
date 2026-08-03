<?php
$pageTitle = $pageTitle ?? 'BKK Community Group';
$activeNav = $activeNav ?? '';
$bodyClass = $bodyClass ?? '';
$user = current_user();
$navItems = [
    'home' => ['index.php', 'Home'],
    'events' => ['events.php', 'Events'],
    'discounts' => ['discounts.php', 'Discounts'],
    'info' => ['info.php', 'Local Info'],
    'contact' => ['contact.php', 'Contact'],
];
?>
<!doctype html>
<html lang="en-ZA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BKK Community Group events, senior discounts and local support information.">
    <meta name="theme-color" content="#1F4E79">
    <title><?= h($pageTitle) ?> | BKK Community</title>
    <link rel="stylesheet" href="<?= h(app_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('assets/css/app.css')) ?>">
</head>
<body class="<?= h($bodyClass) ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header" data-site-header>
    <div class="shell header-inner">
        <a class="brand" href="<?= h(app_url('index.php')) ?>" aria-label="BKK Community Group home">
            <span class="brand-mark" aria-hidden="true">BKK</span>
            <span class="brand-copy"><strong>Community</strong><small>Group</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="primary-navigation" data-nav-toggle>
            <?= icon_svg('menu', 'icon menu-open') ?>
            <?= icon_svg('close', 'icon menu-close') ?>
            <span>Menu</span>
        </button>
        <nav class="primary-navigation" id="primary-navigation" aria-label="Primary navigation" data-navigation>
            <div class="nav-links">
                <?php foreach ($navItems as $key => [$path, $label]): ?>
                    <a href="<?= h(app_url($path)) ?>" <?= $activeNav === $key ? 'aria-current="page"' : '' ?>><?= h($label) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="account-actions">
                <?php if ($user): ?>
                    <?php if (is_admin()): ?>
                        <a class="account-link" href="<?= h(app_url('admin/index.php')) ?>"><?= icon_svg('shield') ?> Admin</a>
                    <?php else: ?>
                        <a class="account-link" href="<?= h(app_url('profile.php')) ?>"><?= icon_svg('user') ?> My profile</a>
                    <?php endif; ?>
                    <form action="<?= h(app_url('actions.php')) ?>" method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="logout">
                        <button class="text-button" type="submit">Log out</button>
                    </form>
                <?php else: ?>
                    <a class="text-button-link" href="<?= h(app_url('login.php')) ?>">Log in</a>
                    <a class="button button-small button-light" href="<?= h(app_url('register.php')) ?>">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<?php if (is_demo_mode()): ?>
    <div class="demo-notice" role="status">
        <div class="shell"><?= icon_svg('info') ?><strong>Demonstration mode:</strong> sample content and browser-session data are being used until MySQL is configured.</div>
    </div>
<?php endif; ?>

<?php $flashes = pull_flashes(); ?>
<?php if ($flashes): ?>
    <div class="flash-region shell" aria-live="polite">
        <?php foreach ($flashes as $message): ?>
            <div class="flash flash-<?= h($message['type']) ?>"><?= icon_svg($message['type'] === 'success' ? 'check' : 'info') ?><span><?= h($message['message']) ?></span></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="main-content">
