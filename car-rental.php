<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/site_svg.php';

$vehicleTypes = [
    'safari-4x4-landcruiser' => [
        'label' => 'Safari 4X4 Landcruiser',
        'body' => 'Pop-up-roof 4x4 Land Cruisers built for game drives and rough park roads, with a driver-guide included.',
    ],
    'vans' => [
        'label' => 'VANs',
        'body' => 'Vans for group travel between towns and destinations -- comfortable for 7-14 people plus luggage.',
    ],
    'airport-transfer' => [
        'label' => 'Airport Transfer',
        'body' => 'Airport pickups and drop-offs, timed to your flight, anywhere in Kampala, Entebbe and beyond.',
    ],
    'luxury-cars' => [
        'label' => 'Luxury Cars',
        'body' => 'Executive sedans and SUVs with a chauffeur, for city travel, meetings, and airport runs in comfort.',
    ],
];

$selectedType = $_GET['type'] ?? '';
if (!isset($vehicleTypes[$selectedType])) {
    $selectedType = '';
}
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

$page_title = 'Car Rental - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Vehicle Hire</p>
    <h1 class="page-header__title">Car Rental</h1>
    <p class="page-header__lead">Self-drive isn't recommended on most East Africa park roads -- every vehicle comes with a driver. Tell us what you need and we'll send a quote.</p>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <div class="card-grid">
      <?php foreach ($vehicleTypes as $slug => $vehicle): ?>
        <div class="tour-card">
          <div style="padding:20px;">
            <div class="why-item__icon" style="margin-bottom:12px;"><?= render_nav_glyph('car') ?></div>
            <h3 class="why-item__title"><?= h($vehicle['label']) ?></h3>
            <p class="why-item__body"><?= h($vehicle['body']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--savanna">
  <div class="wrap" style="max-width:640px;">
    <div class="section__header">
      <p class="section__eyebrow">Request a Quote</p>
      <h2 class="section__title">Tell us what you need</h2>
    </div>
    <?php if ($sent): ?>
      <div class="flash flash--success">Thanks — your car rental request has been sent. We'll be in touch shortly.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/car-rental-submit.php')) ?>">
        <?= csrf_field() ?>
        <div class="site-form__row">
          <label for="vehicle_type">Vehicle type</label>
          <select id="vehicle_type" name="vehicle_type" required>
            <option value="">Select a vehicle&hellip;</option>
            <?php foreach ($vehicleTypes as $slug => $vehicle): ?>
              <option value="<?= h($vehicle['label']) ?>" <?= $selectedType === $slug ? 'selected' : '' ?>><?= h($vehicle['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
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
          <label for="pickup_date">Pickup date</label>
          <input type="date" id="pickup_date" name="pickup_date">
        </div>
        <div class="site-form__row">
          <label for="dropoff_date">Drop-off date</label>
          <input type="date" id="dropoff_date" name="dropoff_date">
        </div>
        <div class="site-form__row">
          <label for="message">Anything else we should know?</label>
          <textarea id="message" name="message" rows="5" placeholder="Number of people, route, luggage..."></textarea>
        </div>
        <button type="submit" class="btn btn--primary">Request Quote</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
