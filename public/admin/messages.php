<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pageTitle = 'Contact messages';
$bodyClass = 'admin-page';
$adminPage = 'messages';
$messages = admin_contact_messages();
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <header class="admin-header">
            <div><p class="eyebrow">Community inbox</p><h1>Contact messages</h1></div>
            <a class="button button-outline" href="<?= h(app_url('contact.php')) ?>">View contact page</a>
        </header>
        <section class="panel">
            <h2>Messages from the website</h2>
            <?php if (!$messages): ?>
                <div class="empty-state"><p>No contact messages have been received yet.</p></div>
            <?php else: ?>
                <div class="table-wrap" role="region" aria-label="Contact messages table" tabindex="0">
                    <table class="data-table">
                        <thead><tr><th>Received</th><th>Sender</th><th>Message</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?= h((new DateTimeImmutable($message['submitted_at']))->format('d M Y, H:i')) ?></td>
                                <td><strong><?= h($message['name']) ?></strong><br><a href="mailto:<?= h($message['email']) ?>"><?= h($message['email']) ?></a><?php if (!empty($message['phone'])): ?><br><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $message['phone'])) ?>"><?= h($message['phone']) ?></a><?php endif; ?></td>
                                <td><strong><?= h($message['subject']) ?></strong><details><summary>Read message</summary><p class="message-copy"><?= nl2br(h($message['message'])) ?></p></details></td>
                                <td>
                                    <form action="<?= h(app_url('actions.php')) ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="admin_update_message">
                                        <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                                        <label class="visually-hidden" for="status-<?= (int) $message['id'] ?>">Status for <?= h($message['subject']) ?></label>
                                        <select id="status-<?= (int) $message['id'] ?>" name="status">
                                            <?php foreach (['new' => 'New', 'read' => 'Read', 'resolved' => 'Resolved'] as $value => $label): ?>
                                                <option value="<?= h($value) ?>" <?= $message['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button button-small" type="submit">Save status</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
