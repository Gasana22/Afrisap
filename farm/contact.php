<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Public page -- no auth-check.php on purpose. No submission form here: the
// schema has no table for storing public contact messages yet. If message
// storage is wanted later, that's a new migration (contact_messages), not
// something to bolt on without one -- flagging per project convention
// rather than inventing a table silently.

$companyStmt = db()->prepare('SELECT setting_value FROM settings WHERE organization_id IS NULL AND setting_key = "company_name"');
$companyStmt->execute();
$companyName = $companyStmt->fetchColumn() ?: APP_NAME;

$pageTitle = 'Contact';
require __DIR__ . '/includes/site_header.php';
?>

<div class="site-main">
    <section class="section reveal">
        <div class="section-head">
            <span class="eyebrow">Get in touch</span>
            <h1>Contact <?= e($companyName) ?></h1>
            <p>Interested in onboarding your farm or organization, or have a question about a traceability result? Reach out and a member of the team will get back to you.</p>
        </div>

        <div class="contact-grid">
            <div class="contact-card">
                <span class="eyebrow">Sales &amp; onboarding</span>
                <h3>Start a new organization</h3>
                <p><a href="mailto:sales@afrisap.example">sales@afrisap.example</a></p>
            </div>
            <div class="contact-card">
                <span class="eyebrow">Support</span>
                <h3>Already running on Afrisap</h3>
                <p><a href="mailto:support@afrisap.example">support@afrisap.example</a></p>
            </div>
            <div class="contact-card">
                <span class="eyebrow">Already a customer?</span>
                <h3>Reach your own team</h3>
                <p><a href="<?= BASE_URL ?>/login.php">Sign in</a> to reach your organization's team directly, or ask your Farm Owner or Manager for help.</p>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
