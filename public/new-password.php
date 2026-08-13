<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Choose a new password';
$activeNav = '';
$resetEmail = normalise_account_email((string) ($_SESSION['password_reset_email'] ?? ''));

require __DIR__ . '/partials/header.php';
?>
<section class="form-page">
    <div class="shell">
        <?php if (is_demo_mode()): ?>
            <div class="form-card form-card-narrow empty-state">
                <h1>Password reset unavailable</h1>
                <p>Password email and reset codes are disabled in demonstration mode.</p>
                <a class="button" href="<?= h(app_url('reset-password.php')) ?>">Request another code</a>
            </div>
        <?php else: ?>
            <form class="form-card form-card-narrow" action="<?= h(app_url('actions.php')) ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="complete_reset">
                <p class="eyebrow">Account recovery</p>
                <h1>Choose a new password</h1>
                <p class="form-intro">Enter the 6-digit code from your email. It expires after 15 minutes and stops working after five incorrect attempts.</p>
                <div class="field"><label for="email">Account email</label><input id="email" name="email" type="email" autocomplete="email" value="<?= h($resetEmail) ?>" required></div>
                <div class="field"><label for="token">6-digit reset code</label><input id="token" name="token" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required></div>
                <p class="form-intro">Your new password must contain at least 8 characters, including a letter and a number.</p>
                <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="128" required></div>
                <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="128" required></div>
                <button class="button button-wide" type="submit">Change password</button>
                <p class="form-links"><a href="<?= h(app_url('reset-password.php')) ?>">Request another code</a></p>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
