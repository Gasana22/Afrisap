<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$operators = db()->query('SELECT o.*, COUNT(t.id) AS tour_count
    FROM tour_operators o
    LEFT JOIN tours t ON t.operator_id = o.id
    GROUP BY o.id
    ORDER BY o.company_name')->fetchAll();

$page_title = 'Tour Operators — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Who Runs The Tours</p>
    <h1 class="page-header__title">Tour Operators</h1>
    <p class="page-header__lead">Every Safarisap tour is run by a named, licensed operator on the ground -- not a call centre. Here's who they are.</p>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:760px;">
    <?php if (!$operators): ?>
      <p class="empty-note">Operator profiles are being added. Check back soon, or <a href="<?= h(url('/contact.php')) ?>">get in touch</a> if you run tours in East Africa and want to be listed.</p>
    <?php else: ?>
      <div class="list-rows">
        <?php foreach ($operators as $operator): ?>
          <a href="<?= h(url('/operator.php?id=' . $operator['id'])) ?>" class="list-row">
            <div style="display:flex;align-items:center;gap:14px;">
              <?php if ($operator['logo_path']): ?>
                <img src="<?= h(url('/' . $operator['logo_path'])) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">
              <?php endif; ?>
              <div>
                <div class="list-row__title"><?= h($operator['company_name']) ?></div>
                <div class="list-row__meta">
                  <?= (int) $operator['tour_count'] ?> tour<?= (int) $operator['tour_count'] === 1 ? '' : 's' ?>
                  <?php if ($operator['phone']): ?> &middot; <?= h($operator['phone']) ?><?php endif; ?>
                  <?php if ($operator['email']): ?> &middot; <?= h($operator['email']) ?><?php endif; ?>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
