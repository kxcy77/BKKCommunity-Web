<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (current_user()) {
    redirect_to('profile.php');
}

$pageTitle = 'Create an account';
$activeNav = '';
require __DIR__ . '/partials/header.php';
?>
<section class="form-page">
    <div class="shell form-layout">
        <form class="form-card" action="<?= h(app_url('actions.php')) ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="register">
            <p class="eyebrow">Free member account</p>
            <h1>Create an account</h1>
            <p class="form-intro">Only essential information is requested. Fields marked with “required” must be completed.</p>
            <div class="form-grid">
                <div class="field field-full">
                    <label for="full_name">Full name <span aria-hidden="true">*</span></label>
                    <input id="full_name" name="full_name" autocomplete="name" minlength="2" maxlength="120" required autofocus>
                </div>
                <div class="field">
                    <label for="email">Email address <span aria-hidden="true">*</span></label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required>
                </div>
                <div class="field">
                    <label for="phone">Phone number</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="30">
                </div>
                <div class="field">
                    <label for="password">Password <span aria-hidden="true">*</span></label>
                    <div class="password-wrap">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required aria-describedby="password-help">
                        <button class="password-toggle" type="button" aria-pressed="false" data-password-toggle="password">Show</button>
                    </div>
                    <small id="password-help">Use at least 10 characters with upper- and lowercase letters and a number.</small>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password <span aria-hidden="true">*</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required>
                </div>
                <div class="field field-full consent-field">
                    <label><input name="privacy_consent" type="checkbox" value="1" required> I understand that my details are used only to manage my account, attendance and requested communication.</label>
                </div>
            </div>
            <button class="button button-wide" type="submit">Create account</button>
            <p class="form-links">Already registered? <a href="<?= h(app_url('login.php')) ?>">Log in</a></p>
        </form>
        <aside class="side-panel">
            <p class="eyebrow eyebrow-light">Privacy first</p>
            <h2>Minimal information, clear purpose</h2>
            <ul class="benefit-list">
                <li><?= icon_svg('shield') ?><span>No identity numbers, financial details or medical records are collected.</span></li>
                <li><?= icon_svg('check') ?><span>You can update your details or delete your account.</span></li>
                <li><?= icon_svg('check') ?><span>Browsing events and information remains available without an account.</span></li>
            </ul>
        </aside>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

