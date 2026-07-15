<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_login();

$tourId = (int) ($_GET['tour_id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$tStmt = db()->prepare('SELECT * FROM tours WHERE id = ?');
$tStmt->execute([$tourId]);
$tour = $tStmt->fetch();

if (!$tour) {
    flash_set('error', 'That tour no longer exists.');
    redirect('/admin/tours/index.php');
}

$faq = ['question' => '', 'answer' => ''];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM tour_faqs WHERE id = ? AND tour_id = ?');
    $stmt->execute([$id, $tourId]);
    $faq = $stmt->fetch();
    if (!$faq) {
        flash_set('error', 'That FAQ no longer exists.');
        redirect('/admin/tours/manage.php?id=' . $tourId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $faq['question'] = trim($_POST['question'] ?? '');
    $faq['answer'] = trim($_POST['answer'] ?? '');

    if ($faq['question'] === '') {
        $errors['question'] = 'Enter a question.';
    }
    if ($faq['answer'] === '') {
        $errors['answer'] = 'Enter an answer.';
    }

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE tour_faqs SET question = ?, answer = ? WHERE id = ?')
                ->execute([$faq['question'], $faq['answer'], $id]);
        } else {
            $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM tour_faqs WHERE tour_id = ?');
            $stmt->execute([$tourId]);
            $nextOrder = (int) $stmt->fetchColumn();
            db()->prepare('INSERT INTO tour_faqs (tour_id, question, answer, sort_order) VALUES (?, ?, ?, ?)')
                ->execute([$tourId, $faq['question'], $faq['answer'], $nextOrder]);
        }
        flash_set('success', 'FAQ saved.');
        redirect('/admin/tours/manage.php?id=' . $tourId);
    }
}

$page_title = ($id ? 'Edit' : 'Add') . ' FAQ · ' . $tour['title'];
$page_eyebrow = 'Safari';
$active_nav = 'tours';

require __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field form-field--full<?= isset($errors['question']) ? ' has-error' : '' ?>">
          <label for="question">Question</label>
          <input type="text" id="question" name="question" value="<?= h($faq['question']) ?>" autofocus required>
          <?php if (isset($errors['question'])): ?><span class="error-text"><?= h($errors['question']) ?></span><?php endif; ?>
        </div>
        <div class="form-field form-field--full<?= isset($errors['answer']) ? ' has-error' : '' ?>">
          <label for="answer">Answer</label>
          <textarea id="answer" name="answer"><?= h($faq['answer']) ?></textarea>
          <?php if (isset($errors['answer'])): ?><span class="error-text"><?= h($errors['answer']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save FAQ</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/tours/manage.php?id=' . $tourId)) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
