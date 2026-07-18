<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$images = db()->query("
    (SELECT m.file_path, m.caption, d.name AS title, CONCAT('/destination.php?id=', d.id) AS link, m.created_at
        FROM media m JOIN destinations d ON d.id = m.entity_id AND m.entity_type = 'destination')
    UNION ALL
    (SELECT m.file_path, m.caption, t.title, CONCAT('/tour.php?id=', t.id), m.created_at
        FROM media m JOIN tours t ON t.id = m.entity_id AND m.entity_type = 'tour' WHERE t.status = 'published')
    UNION ALL
    (SELECT m.file_path, m.caption, ed.name, CONCAT('/experience-destination.php?id=', ed.id), m.created_at
        FROM media m JOIN experience_destinations ed ON ed.id = m.entity_id AND m.entity_type = 'experience_destination')
    UNION ALL
    (SELECT m.file_path, m.caption, et.title, CONCAT('/experience-tour.php?id=', et.id), m.created_at
        FROM media m JOIN experience_tours et ON et.id = m.entity_id AND m.entity_type = 'experience_tour' WHERE et.status = 'published')
    ORDER BY created_at DESC
    LIMIT 60
")->fetchAll();

$page_title = 'Gallery - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">In Pictures</p>
    <h1 class="page-header__title">Gallery</h1>
    <p class="page-header__lead">A look at the parks, cultures and journeys Safarisap plans.</p>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <?php if (!$images): ?>
      <p class="empty-note">The gallery is filling up as tours and destinations go live — check back soon.</p>
    <?php else: ?>
      <div class="gallery-strip" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
        <?php foreach ($images as $img): ?>
          <a href="<?= h(url($img['link'])) ?>" style="position:relative;display:block;">
            <img src="<?= h(url('/' . $img['file_path'])) ?>" alt="<?= h($img['caption'] ?? $img['title']) ?>" loading="lazy" style="height:200px;">
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
