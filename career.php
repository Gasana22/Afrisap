<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/uploads.php';
require_once __DIR__ . '/includes/mailer.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM careers WHERE id = ?');
$stmt->execute([$id]);
$career = $stmt->fetch();

if (!$career) {
    http_response_code(404);
    $page_title = 'Listing not found — Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That listing isn\'t available. <a href="' . h(url('/careers.php')) . '">See open roles</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $coverLetter = trim($_POST['cover_letter'] ?? '');

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['form'] = 'Enter your name and a valid email.';
    }

    $cvPath = null;
    try {
        $cvPath = handle_document_upload('cv');
    } catch (RuntimeException $e) {
        // CV upload is optional; only a real upload attempt that fails is an error.
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['cv'] = $e->getMessage();
        }
    }

    if (!$errors) {
        db()->prepare('INSERT INTO career_applications (career_id, full_name, email, phone, cv_path, cover_letter) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$id, $name, $email, $phone, $cvPath, $coverLetter]);

        send_email(
            $email,
            'We received your application — ' . $career['title'],
            "Hi $name,\n\nThanks for applying for {$career['title']}. We'll review your application and be in touch.\n\n— Safarisap"
        );
        notify_admin(
            'New application: ' . $career['title'] . ' from ' . $name,
            "New career application\n\nRole: {$career['title']}\nName: $name\nEmail: $email\nPhone: $phone\nCV attached: " . ($cvPath ? 'Yes' : 'No') . "\nCover letter:\n$coverLetter"
        );

        redirect('/career.php?id=' . $id . '&sent=1');
    }
}

$page_title = $career['title'] . ' — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Careers</p>
    <h1 class="page-header__title"><?= h($career['title']) ?></h1>
    <?php if ($career['location']): ?><p class="page-header__lead"><?= h($career['location']) ?></p><?php endif; ?>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:640px;">
    <?php if ($career['description']): ?><p class="detail-text" style="font-size:15.5px;margin-bottom:32px;"><?= nl2br(h($career['description'])) ?></p><?php endif; ?>

    <h2 class="detail-heading" style="margin-top:0;">Apply</h2>
    <?php if ($sent): ?>
      <div class="flash flash--success">Thanks — your application has been received.</div>
    <?php else: ?>
      <?php if (isset($errors['form'])): ?><div class="flash flash--error"><?= h($errors['form']) ?></div><?php endif; ?>
      <form class="site-form" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="site-form__row">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>
        <div class="site-form__row">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="site-form__row">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone">
        </div>
        <div class="site-form__row">
          <label for="cv">CV / Resume (PDF, JPG, PNG or WEBP, up to 5MB)</label>
          <input type="file" id="cv" name="cv" accept="application/pdf,image/jpeg,image/png,image/webp">
          <?php if (isset($errors['cv'])): ?><span class="error-text"><?= h($errors['cv']) ?></span><?php endif; ?>
        </div>
        <div class="site-form__row">
          <label for="cover_letter">Cover letter</label>
          <textarea id="cover_letter" name="cover_letter" rows="5"></textarea>
        </div>
        <button type="submit" class="btn btn--primary">Send Application</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
