<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Contact us';
$activeNav = 'contact';
$user = current_user();
$subject = trim((string) ($_GET['subject'] ?? 'General enquiry'));
require __DIR__ . '/partials/header.php';
?>
<section class="page-hero"><div class="shell"><p class="eyebrow eyebrow-light">We are here to help</p><h1>Contact BKK Community</h1><p>Send one clear message to the community team. We collect only the information needed to respond.</p></div></section>
<section class="form-page">
    <div class="shell form-layout">
        <form class="form-card" action="<?= h(app_url('actions.php')) ?>" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="contact">
            <h2>Send us a message</h2><p class="form-intro">Required fields are marked “required”. We aim to respond within two working days.</p>
            <div class="form-grid">
                <div class="field"><label for="name">Full name <span aria-hidden="true">*</span></label><input id="name" name="name" autocomplete="name" required minlength="2" value="<?= h($user['full_name'] ?? '') ?>"></div>
                <div class="field"><label for="email">Email address <span aria-hidden="true">*</span></label><input id="email" name="email" type="email" autocomplete="email" required value="<?= h($user['email'] ?? '') ?>"></div>
                <div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" autocomplete="tel" value="<?= h($user['phone'] ?? '') ?>"></div>
                <div class="field"><label for="subject">Subject <span aria-hidden="true">*</span></label><select id="subject" name="subject" required><option <?= $subject === 'General enquiry' ? 'selected' : '' ?>>General enquiry</option><option <?= str_starts_with($subject, 'Event') ? 'selected' : '' ?>>Event enquiry</option><option <?= str_starts_with($subject, 'Discount') ? 'selected' : '' ?>>Discount enquiry</option><option <?= $subject === 'Local service enquiry' ? 'selected' : '' ?>>Local service enquiry</option><option <?= $subject === 'Website support' ? 'selected' : '' ?>>Website support</option></select></div>
                <div class="field field-full"><label for="message">Message <span aria-hidden="true">*</span></label><textarea id="message" name="message" required minlength="10" maxlength="2000" placeholder="Please tell us how we can help."></textarea><small>10–2,000 characters. Do not include identity numbers, medical records or banking information.</small></div>
            </div>
            <button class="button" type="submit"><?= icon_svg('mail') ?> Send message</button>
        </form>
        <aside class="side-panel">
            <p class="eyebrow eyebrow-light">Direct contact</p><h2>Prefer to speak to someone?</h2><p>Use the details below during ordinary community-office hours.</p>
            <ul class="benefit-list"><li><?= icon_svg('phone') ?><span><strong>Phone</strong><br><a class="footer-link" href="tel:+27728885030">072 888 5030</a></span></li><li><?= icon_svg('mail') ?><span><strong>Email</strong><br><a class="footer-link" href="mailto:andrew.spaumer@gmail.com">andrew.spaumer@gmail.com</a></span></li><li><?= icon_svg('pin') ?><span><strong>Location</strong><br>BKK Community Hall</span></li></ul>
        </aside>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

