<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (current_user()) {
    redirect_to(is_admin() ? 'admin/index.php' : 'profile.php');
}

$pageTitle = 'Log in';
$activeNav = '';
require __DIR__ . '/partials/header.php';
?>
<section class="form-page">
    <div class="shell form-layout">
        <form class="form-card" action="<?= h(app_url('actions.php')) ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="login">
            <p class="eyebrow">Member access</p>
            <h1>Welcome back</h1>
            <p class="form-intro">Log in to confirm attendance, manage reminders and view your saved events.</p>
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" aria-pressed="false" data-password-toggle="password">Show</button>
                </div>
            </div>
            <p><a href="<?= h(app_url('reset-password.php')) ?>">Forgot your password?</a></p>
            <button class="button button-wide" type="submit">Log in</button>
            <p class="form-links">Do not have an account? <a href="<?= h(app_url('register.php')) ?>">Register here</a></p>
        </form>
        <aside class="side-panel">
            <p class="eyebrow eyebrow-light">Why create an account?</p>
            <h2>Keep your plans in one place</h2>
            <ul class="benefit-list">
                <li><?= icon_svg('check') ?><span>Confirm or cancel event attendance.</span></li>
                <li><?= icon_svg('check') ?><span>See your upcoming event list.</span></li>
                <li><?= icon_svg('check') ?><span>Control reminder preferences.</span></li>
            </ul>
            <?php if (is_demo_mode()): ?>
                <div class="demo-credentials">
                    <strong>Member demo</strong><code>member@bkk.demo</code><code>MemberDemo!26</code>
                    <br><strong>Administrator demo</strong><code>admin@bkk.demo</code><code>AdminDemo!26</code>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

