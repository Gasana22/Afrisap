<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$plans = db_all("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");

render_header(['title' => 'Pricing', 'context' => 'public']);
render_public_navbar();
?>
<section class="hero-section py-5">
  <div class="container text-center">
    <h1 class="fw-bold">Simple, transparent pricing</h1>
    <p class="lead">Pick the plan that fits your operation today &mdash; upgrade any time as you grow.</p>
    <div class="d-flex justify-content-center align-items-center gap-3 mt-4">
      <span class="text-white">Monthly</span>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="billingToggle" style="width: 3em; height: 1.5em;">
      </div>
      <span class="text-white">Yearly <span class="badge bg-warning text-dark">2 months free</span></span>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <?php if (!$plans): ?>
      <?php render_empty_state('No subscription plans are available right now. Please check back soon.'); ?>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($plans as $plan):
            $features = json_decode($plan['features'] ?? '[]', true) ?: [];
            $isFeatured = strtolower($plan['slug']) === 'professional';
            $monthly = (float) $plan['price'];
            $yearly = $monthly * 10; // 2 months free
        ?>
          <div class="col-md-6 col-lg-3">
            <div class="pricing-card<?= $isFeatured ? ' featured' : '' ?>">
              <?php if ($isFeatured): ?>
                <span class="badge bg-primary mb-2">Most Popular</span>
              <?php endif; ?>
              <h2 class="h5 fw-bold"><?= e($plan['name']) ?></h2>
              <div class="price mt-2">
                $<span data-price-monthly="<?= number_format($monthly, 2) ?>" data-price-yearly="<?= number_format($yearly, 2) ?>"><?= number_format($monthly, 2) ?></span>
                <span class="fs-6 text-muted fw-normal">/<?= $monthly > 0 ? 'mo' : 'forever' ?></span>
              </div>
              <ul class="list-unstyled mt-3 mb-4">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Up to <?= (int) $plan['max_farms'] ?> farms</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Up to <?= (int) $plan['max_users'] ?> team members</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i><?= (int) $plan['max_storage_mb'] ?> MB storage</li>
                <?php foreach ($features as $feature): ?>
                  <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i><?= e(humanize((string) $feature)) ?></li>
                <?php endforeach; ?>
              </ul>
              <a href="<?= base_url('public/register.php') ?>" class="btn <?= $isFeatured ? 'btn-primary' : 'btn-outline-primary' ?> w-100">Get Started</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="text-center text-muted mt-5">Need a custom plan for a large operation? <a href="<?= base_url('public/contact.php') ?>">Talk to us</a>.</p>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
