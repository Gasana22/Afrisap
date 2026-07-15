<?php
require_once __DIR__ . '/../includes/bootstrap.php';

render_header(['title' => 'Privacy Policy', 'context' => 'public']);
render_public_navbar();
?>
<section class="py-5">
  <div class="container" style="max-width: 860px;">
    <h1 class="fw-bold mb-2">Privacy Policy</h1>
    <p class="text-muted mb-5">Last updated: <?= date('d M Y') ?>. This is a placeholder policy for demonstration
      purposes and does not constitute legal advice.</p>

    <h4 class="fw-semibold mt-4">1. Information We Collect</h4>
    <p class="text-muted">We collect information you provide directly, such as your name, email address, phone
      number, organization details, and any farm, crop, livestock or traceability data you enter into the
      platform. We also collect limited technical information (IP address, browser type, pages visited) to keep
      the service secure and reliable.</p>

    <h4 class="fw-semibold mt-4">2. How We Use Your Information</h4>
    <p class="text-muted">We use collected information to provide and improve the Smart Farm Platform service,
      respond to support and demo requests, send account-related notifications, and maintain the security and
      integrity of the platform. We do not sell your personal information to third parties.</p>

    <h4 class="fw-semibold mt-4">3. Data Sharing</h4>
    <p class="text-muted">Traceability data you choose to publish (such as batch and product journey information
      linked to a QR code) is intentionally public and viewable by anyone who scans the code. All other account
      and operational data remains private to your organization and is only shared with service providers who
      help us operate the platform (e.g. hosting, email delivery) under confidentiality obligations.</p>

    <h4 class="fw-semibold mt-4">4. Data Retention</h4>
    <p class="text-muted">We retain account and operational data for as long as your organization maintains an
      active subscription, plus a reasonable period afterwards for legal, accounting and backup purposes. You may
      request deletion of your account data at any time, subject to records we are required to keep.</p>

    <h4 class="fw-semibold mt-4">5. Security</h4>
    <p class="text-muted">We use industry-standard measures, including encrypted connections and hashed
      passwords, to protect your information. No system is completely secure, and we encourage you to use a
      strong, unique password for your account.</p>

    <h4 class="fw-semibold mt-4">6. Your Choices</h4>
    <p class="text-muted">You may access, correct, or request deletion of your personal data by contacting us
      using the details below. Organization administrators can manage most of their own data directly within the
      platform.</p>

    <h4 class="fw-semibold mt-4">7. Contact Us</h4>
    <p class="text-muted">Questions about this policy can be sent to
      <a href="mailto:privacy@smartfarmplatform.example">privacy@smartfarmplatform.example</a> or via our
      <a href="<?= base_url('public/contact.php') ?>">contact form</a>.</p>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
