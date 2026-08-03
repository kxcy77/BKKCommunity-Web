<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Choose a new password';
$activeNav = '';
$token = trim((string) ($_GET['token'] ?? ''));
$tokenFormatIsValid = (bool) preg_match('/^[a-f0-9]{64}$/', $token);

require __DIR__ . '/partials/header.php';
?>
<section class="form-page">
    <div class="shell">
        <?php if (is_demo_mode() || !$tokenFormatIsValid): ?>
            <div class="form-card form-card-narrow empty-state">
                <h1>Reset link unavailable</h1>
                <p><?= is_demo_mode() ? 'Password email and reset links are disabled in demonstration mode.' : 'This password-reset link is incomplete or invalid.' ?></p>
                <a class="button" href="<?= h(app_url('reset-password.php')) ?>">Request another link</a>
            </div>
        <?php else: ?>
            <form class="form-card form-card-narrow" action="<?= h(app_url('actions.php')) ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="complete_reset">
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <p class="eyebrow">Account recovery</p>
                <h1>Choose a new password</h1>
                <p class="form-intro">Use at least 10 characters with upper- and lowercase letters and a number.</p>
                <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required></div>
                <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required></div>
                <button class="button button-wide" type="submit">Change password</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

