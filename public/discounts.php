<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Senior discounts';
$activeNav = 'discounts';
$allDiscounts = all_discounts();
$categories = array_values(array_unique(array_column($allDiscounts, 'category')));
sort($categories);
$selectedCategory = trim((string) ($_GET['category'] ?? 'All'));
$discounts = $selectedCategory === 'All'
    ? $allDiscounts
    : array_values(array_filter($allDiscounts, fn (array $discount): bool => $discount['category'] === $selectedCategory));

require __DIR__ . '/partials/header.php';
?>
<section class="page-hero">
    <div class="shell">
        <p class="eyebrow eyebrow-light">Spend wisely</p>
        <h1>Senior discounts and savings</h1>
        <p>Compare offers by category, check eligibility and read the claim instructions before visiting a provider.</p>
    </div>
</section>
<section class="section section-gold">
    <div class="shell">
        <div class="flash flash-info discount-warning" role="note">
            <?= icon_svg('info') ?>
            <span><strong>Please verify before travelling.</strong> The entries below are demonstration content and provider terms can change.</span>
        </div>
        <nav class="filter-bar" aria-label="Filter discounts by category">
            <a class="filter-pill" href="<?= h(app_url('discounts.php')) ?>" <?= $selectedCategory === 'All' ? 'aria-current="true"' : '' ?>>All</a>
            <?php foreach ($categories as $category): ?>
                <a class="filter-pill" href="<?= h(app_url('discounts.php?category=' . rawurlencode($category))) ?>" <?= $selectedCategory === $category ? 'aria-current="true"' : '' ?>><?= h($category) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if (!$discounts): ?>
            <div class="empty-state"><h2>No discounts found</h2><p>Choose another category or view all discounts.</p></div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($discounts as $discount): ?>
                    <article class="content-card tone-<?= h($discount['tone']) ?>">
                        <header class="content-card-header">
                            <h2><?= h($discount['store_name']) ?></h2>
                            <span class="category-badge"><?= h($discount['category']) ?></span>
                        </header>
                        <div class="content-card-body">
                            <p class="deal"><?= h($discount['deal']) ?></p>
                            <dl class="detail-list">
                                <div><dt>Who qualifies</dt><dd><?= h($discount['eligibility']) ?></dd></div>
                                <div><dt>How to claim</dt><dd><?= h($discount['claim_instructions']) ?></dd></div>
                            </dl>
                            <a class="button button-outline" href="<?= h(app_url('contact.php?subject=' . rawurlencode('Discount enquiry: ' . $discount['store_name']))) ?>">Ask for help</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

