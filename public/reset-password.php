<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Reset your password';
$activeNav = '';
require __DIR__ . '/partials/header.php';
?>
<section class="form-page">
    <div class="shell">
        <form class="form-card form-card-narrow" action="<?= h(app_url('actions.php')) ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="request_reset">
            <p class="eyebrow">Account recovery</p>
            <h1>Reset your password</h1>
            <p class="form-intro">Enter your account email to request a 6-digit code. For privacy, the confirmation message is the same whether or not an account exists.</p>
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required autofocus>
            </div>
            <button class="button button-wide" type="submit">Send 6-digit code</button>
            <p class="form-links"><a href="<?= h(app_url('login.php')) ?>">Back to log in</a></p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
