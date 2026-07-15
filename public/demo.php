<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$old = [];

if (is_post() && csrf_verify()) {
    $data = [
        'name' => clean_string($_POST['name'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'phone' => clean_string($_POST['phone'] ?? ''),
        'organization_name' => clean_string($_POST['organization_name'] ?? ''),
        'farm_size' => clean_string($_POST['farm_size'] ?? ''),
        'message' => clean_string($_POST['message'] ?? ''),
    ];

    $errors = validate($data, [
        'name' => 'required|max:255',
        'email' => 'required|email',
        'organization_name' => 'required|max:255',
    ]);

    if (!$errors) {
        db_insert('demo_requests', [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'organization_name' => $data['organization_name'],
            'farm_size' => $data['farm_size'] ?: null,
            'message' => $data['message'] ?: null,
            'status' => 'new',
        ]);

        session_flash('success', "Thanks! We've received your demo request and will reach out shortly.");
        redirect('public/demo.php');
    }

    $old = $data;
} elseif (is_post()) {
    $errors[] = 'Your session expired, please try again.';
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Request a Demo', 'context' => 'public']);
render_public_navbar();
?>
<section class="hero-section py-5">
  <div class="container text-center">
    <h1 class="fw-bold">See Smart Farm Platform in action</h1>
    <p class="lead">Tell us a little about your operation and we'll set up a personalized walkthrough.</p>
  </div>
</section>

<section class="py-5">
  <div class="container" style="max-width: 720px;">
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
          <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= e($old['phone'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Organization Name</label>
            <input type="text" name="organization_name" class="form-control" required value="<?= e($old['organization_name'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Approximate Farm Size</label>
            <input type="text" name="farm_size" class="form-control" placeholder="e.g. 25 hectares" value="<?= e($old['farm_size'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Anything else we should know?</label>
            <textarea name="message" class="form-control" rows="4"><?= e($old['message'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Request Demo</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
