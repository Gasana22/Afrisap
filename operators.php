<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$operators = db()->query("SELECT o.*, c.name AS country_name,
        COUNT(DISTINCT t.id) AS tour_count,
        MIN(t.price) AS price_min, MAX(t.price) AS price_max
    FROM tour_operators o
    LEFT JOIN countries c ON c.id = o.country_id
    LEFT JOIN tours t ON t.operator_id = o.id AND t.status = 'published'
    GROUP BY o.id
    ORDER BY o.company_name")->fetchAll();

$destinationsStmt = db()->prepare("SELECT DISTINCT country_name FROM (
        SELECT dc.name AS country_name FROM tours t
            JOIN tour_destinations td ON td.tour_id = t.id
            JOIN destinations d ON d.id = td.destination_id
            JOIN countries dc ON dc.id = d.country_id
            WHERE t.operator_id = ? AND t.status = 'published'
        UNION
        SELECT tc_c.name AS country_name FROM tours t
            JOIN tour_countries tc ON tc.tour_id = t.id
            JOIN countries tc_c ON tc_c.id = tc.country_id
            WHERE t.operator_id = ? AND t.status = 'published'
    ) x ORDER BY country_name");

$toursStmt = db()->prepare("SELECT id, title, days, price FROM tours WHERE operator_id = ? AND status = 'published' ORDER BY created_at DESC LIMIT 3");

foreach ($operators as &$operator) {
    $destinationsStmt->execute([$operator['id'], $operator['id']]);
    $operator['destination_countries'] = array_column($destinationsStmt->fetchAll(), 'country_name');

    $toursStmt->execute([$operator['id']]);
    $operator['sample_tours'] = $toursStmt->fetchAll();
}
unset($operator);

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
  <div class="wrap">
    <?php if (!$operators): ?>
      <p class="empty-note">Operator profiles are being added. Check back soon, or <a href="<?= h(url('/contact.php')) ?>">get in touch</a> if you run tours in East Africa and want to be listed.</p>
    <?php else: ?>
      <p class="operator-list__count"><?= count($operators) ?> tour operator<?= count($operators) === 1 ? '' : 's' ?>, travel agencies and tourism companies</p>
      <div class="operator-list">
        <?php foreach ($operators as $operator): ?>
          <div class="operator-card">
            <div class="operator-card__main">
              <?php if ($operator['profile_image_path']): ?>
                <div class="operator-card__photo"><img src="<?= h(url('/' . $operator['profile_image_path'])) ?>" alt="" loading="lazy"></div>
              <?php endif; ?>

              <div class="operator-card__body">
                <a href="<?= h(url('/operator.php?id=' . $operator['id'])) ?>" class="operator-card__title"><?= h($operator['company_name']) ?></a>

                <dl class="operator-card__facts">
                  <?php if ($operator['country_name']): ?>
                    <div><dt>Office in</dt><dd><?= country_flag($operator['country_name']) ?> <?= h($operator['country_name']) ?></dd></div>
                  <?php endif; ?>
                  <?php if ($operator['price_min'] !== null): ?>
                    <div><dt>Price range</dt><dd>$<?= number_format((float) $operator['price_min'], 0) ?> to $<?= number_format((float) $operator['price_max'], 0) ?> per person <span class="operator-card__unit">(USD)</span></dd></div>
                  <?php endif; ?>
                  <?php if ($operator['tour_type']): ?>
                    <div><dt>Tour types</dt><dd><?= h($operator['tour_type']) ?></dd></div>
                  <?php endif; ?>
                  <?php if ($operator['destination_countries']): ?>
                    <div><dt>Destinations</dt><dd class="operator-card__flags">
                      <?php foreach ($operator['destination_countries'] as $countryName): ?>
                        <span class="operator-card__flag"><?= country_flag($countryName) ?> <?= h($countryName) ?></span>
                      <?php endforeach; ?>
                    </dd></div>
                  <?php endif; ?>
                </dl>
              </div>

              <?php if ($operator['logo_path']): ?>
                <img class="operator-card__logo" src="<?= h(url('/' . $operator['logo_path'])) ?>" alt="<?= h($operator['company_name']) ?> logo">
              <?php endif; ?>
            </div>

            <?php if ($operator['sample_tours']): ?>
              <div class="operator-card__tours">
                <?php foreach ($operator['sample_tours'] as $tour): ?>
                  <a href="<?= h(url('/tour.php?id=' . $tour['id'])) ?>">&rsaquo; <?= h($tour['title']) ?> <span class="operator-card__tour-price">(from $<?= number_format((float) $tour['price'], 0) ?> pp)</span></a>
                <?php endforeach; ?>
                <div class="operator-card__cta">
                  <a href="<?= h(url('/operator.php?id=' . $operator['id'])) ?>" class="btn btn--primary btn--sm">All <?= (int) $operator['tour_count'] ?> Tour<?= (int) $operator['tour_count'] === 1 ? '' : 's' ?> &rsaquo;</a>
                  <span class="operator-card__offered-by">&mdash; Offered by <?= h($operator['company_name']) ?></span>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
