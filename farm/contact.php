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

<section class="section">
    <h1>Contact <?= e($companyName) ?></h1>
    <p>Interested in onboarding your farm or organization, or have a question about a traceability result? Reach out and a member of the team will get back to you.</p>

    <div class="card-grid">
        <div class="card">
            <h3>Sales &amp; onboarding</h3>
            <p>sales@afrisap.example</p>
        </div>
        <div class="card">
            <h3>Support</h3>
            <p>support@afrisap.example</p>
        </div>
        <div class="card">
            <h3>Already a customer?</h3>
            <p><a href="<?= BASE_URL ?>/login.php">Sign in</a> to reach your organization's team directly, or ask your Farm Owner/Manager for help.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
