<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Local information';
$activeNav = 'info';
$allServices = all_services();
$categories = array_values(array_unique(array_column($allServices, 'category')));
sort($categories);
$selectedCategory = trim((string) ($_GET['category'] ?? 'All'));
$services = $selectedCategory === 'All'
    ? $allServices
    : array_values(array_filter($allServices, fn (array $service): bool => $service['category'] === $selectedCategory));

require __DIR__ . '/partials/header.php';
?>
<section class="page-hero">
    <div class="shell">
        <p class="eyebrow eyebrow-light">Useful contacts nearby</p>
        <h1>Local information and support</h1>
        <p>Find key phone numbers, locations and opening times without searching across multiple websites.</p>
    </div>
</section>
<section class="section section-blue">
    <div class="shell">
        <nav class="filter-bar" aria-label="Filter local services by category">
            <a class="filter-pill" href="<?= h(app_url('info.php')) ?>" <?= $selectedCategory === 'All' ? 'aria-current="true"' : '' ?>>All services</a>
            <?php foreach ($categories as $category): ?>
                <a class="filter-pill" href="<?= h(app_url('info.php?category=' . rawurlencode($category))) ?>" <?= $selectedCategory === $category ? 'aria-current="true"' : '' ?>><?= h($category) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="card-grid">
            <?php foreach ($services as $service): ?>
                <article class="content-card service-card tone-<?= h($service['tone']) ?>">
                    <header class="content-card-header">
                        <span class="feature-icon"><?= icon_svg($service['category'] === 'Emergency' ? 'shield' : 'pin') ?></span>
                        <div><h2><?= h($service['name']) ?></h2><span><?= h($service['category']) ?></span></div>
                    </header>
                    <div class="content-card-body">
                        <p><?= h($service['description']) ?></p>
                        <dl class="detail-list">
                            <div><dt>Phone</dt><dd><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $service['phone'])) ?>"><?= h($service['phone']) ?></a></dd></div>
                            <div><dt>Location</dt><dd><?= h($service['address']) ?></dd></div>
                            <div><dt>Available</dt><dd><?= h($service['hours']) ?></dd></div>
                        </dl>
                        <a class="button" href="tel:<?= h(preg_replace('/[^0-9+]/', '', $service['phone'])) ?>"><?= icon_svg('phone') ?> Call this service</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

