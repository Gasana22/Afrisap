<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$old = [];

if (is_post() && csrf_verify()) {
    $data = [
        'name' => clean_string($_POST['name'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'subject' => clean_string($_POST['subject'] ?? ''),
        'message' => clean_string($_POST['message'] ?? ''),
    ];

    $errors = validate($data, [
        'name' => 'required|max:255',
        'email' => 'required|email',
        'message' => 'required',
    ]);

    if (!$errors) {
        db_insert('contact_messages', [
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'] ?: null,
            'message' => $data['message'],
            'status' => 'new',
        ]);

        session_flash('success', 'Thanks, we will be in touch!');
        redirect('public/contact.php');
    }

    $old = $data;
} elseif (is_post()) {
    $errors[] = 'Your session expired, please try again.';
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Contact Us', 'context' => 'public']);
render_public_navbar();
?>
<section class="hero-section py-5">
  <div class="container text-center">
    <h1 class="fw-bold">Get in touch</h1>
    <p class="lead">Questions about the platform? Our team is happy to help.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4">
        <h5 class="fw-semibold mb-3">Contact Information</h5>
        <p class="text-muted"><i class="bi bi-geo-alt me-2 text-primary"></i>123 Harvest Road, Kigali, Rwanda</p>
        <p class="text-muted"><i class="bi bi-telephone me-2 text-primary"></i>+250 700 000 000</p>
        <p class="text-muted"><i class="bi bi-envelope me-2 text-primary"></i>hello@smartfarmplatform.example</p>
        <p class="text-muted"><i class="bi bi-clock me-2 text-primary"></i>Mon &ndash; Fri, 8:00 &ndash; 17:00 CAT</p>
      </div>
      <div class="col-lg-8">
        <div class="feature-card">
          <?php render_alerts(); ?>
          <form method="post" data-confirm-submit>
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required value="<?= e($old['name'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" required value="<?= e($old['email'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" value="<?= e($old['subject'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="5" required><?= e($old['message'] ?? '') ?></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary">Send Message</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
