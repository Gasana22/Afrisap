<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/media.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT sp.*, ety.name AS type_name
    FROM service_providers sp
    JOIN experience_types ety ON ety.id = sp.experience_type_id
    WHERE sp.id = ?');
$stmt->execute([$id]);
$provider = $stmt->fetch();

if (!$provider) {
    http_response_code(404);
    $page_title = 'Provider not found - Safarisap';
    require __DIR__ . '/includes/site_header.php';
    echo '<div class="wrap section"><p class="empty-note">That service provider isn\'t available. <a href="' . h(url('/experiences.php')) . '">Browse experiences</a>.</p></div>';
    require __DIR__ . '/includes/site_footer.php';
    exit;
}

$tours = db()->prepare("SELECT et.id, et.title, et.price, et.discount_percent, et.days, et.short_overview
    FROM experience_tours et
    WHERE et.provider_id = ? AND et.status = 'published'
    ORDER BY et.created_at DESC");
$tours->execute([$id]);
$tours = $tours->fetchAll();

$page_title = $provider['company_name'] . ' - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Service Provider</p>
    <h1 class="page-header__title"><?= h($provider['company_name']) ?></h1>
    <p class="page-header__lead"><?= h($provider['type_name']) ?></p>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <div class="detail-grid">
      <div>
        <?php if ($tours): ?>
          <h2 style="font-family:var(--font-display);font-size:20px;margin:0 0 16px;">Tours by <?= h($provider['company_name']) ?></h2>
          <div class="card-grid">
            <?php foreach ($tours as $tour): ?>
              <a href="<?= h(url('/experience-tour.php?id=' . $tour['id'])) ?>" class="tour-card">
                <div class="tour-card__media">
                  <?php if ($cover = get_cover_image('experience_tour', $tour['id'])): ?><img src="<?= h(url('/' . $cover)) ?>" alt="" loading="lazy"><?php endif; ?>
                  <span class="tour-card__badge"><?= h($provider['type_name']) ?></span>
                </div>
                <div class="tour-card__body">
                  <p class="tour-card__meta"><?= (int) $tour['days'] ?> days</p>
                  <h3 class="tour-card__title"><?= h($tour['title']) ?></h3>
                  <p class="tour-card__overview"><?= h(mb_strimwidth((string) $tour['short_overview'], 0, 110, '…')) ?></p>
                  <div class="tour-card__footer">
                    <div class="tour-card__price">
                      <span class="tour-card__price-label">Starting from</span>$<?= number_format((float) $tour['price'] * (1 - (float) $tour['discount_percent'] / 100), 0) ?><span class="tour-card__price-unit">/ person</span>
                    </div>
                    <span class="btn btn--dark" style="padding:8px 14px;font-size:13px;">View Details</span>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="empty-note">No published tours are linked to this provider yet.</p>
        <?php endif; ?>
      </div>

      <aside class="detail-side">
        <div class="side-card">
          <p class="side-card__title"><?= render_nav_glyph('briefcase') ?>Company details</p>
          <?php if ($provider['logo_path']): ?><img src="<?= h(url('/' . $provider['logo_path'])) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;margin-bottom:10px;"><?php endif; ?>
          <?php if ($provider['region']): ?><p class="side-card__body"><strong>Region:</strong> <?= h($provider['region']) ?></p><?php endif; ?>
          <?php if ($provider['contact_person']): ?><p class="side-card__body"><strong>Contact:</strong> <?= h($provider['contact_person']) ?></p><?php endif; ?>
          <?php if ($provider['phone']): ?><p class="side-card__body"><strong>Phone:</strong> <?= h($provider['phone']) ?></p><?php endif; ?>
          <?php if ($provider['email']): ?><p class="side-card__body"><strong>Email:</strong> <a href="mailto:<?= h($provider['email']) ?>"><?= h($provider['email']) ?></a></p><?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
