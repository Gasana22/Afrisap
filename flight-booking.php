<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/site_svg.php';

$flightTypes = [
    'internal-flights' => [
        'label' => 'Internal Flights',
        'body' => 'Scheduled domestic flights between hubs and remote airstrips near the national parks -- saves a long drive when time is tight.',
    ],
    'chartered-flights' => [
        'label' => 'Chartered Flights',
        'body' => 'Private chartered aircraft for your group only, on your own schedule -- ideal for tight itineraries or hard-to-reach airstrips.',
    ],
];

$selectedType = $_GET['type'] ?? '';
if (!isset($flightTypes[$selectedType])) {
    $selectedType = '';
}
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

$page_title = 'Flight Booking — Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Air Travel</p>
    <h1 class="page-header__title">Flight Booking</h1>
    <p class="page-header__lead">Skip the long drive between destinations, or fly private on your own schedule. Tell us what you need and we'll send a quote.</p>
  </div>
</header>

<section class="section">
  <div class="wrap">
    <div class="card-grid">
      <?php foreach ($flightTypes as $slug => $flight): ?>
        <div class="tour-card">
          <div style="padding:20px;">
            <div class="why-item__icon" style="margin-bottom:12px;"><?= render_nav_glyph('plane') ?></div>
            <h3 class="why-item__title"><?= h($flight['label']) ?></h3>
            <p class="why-item__body"><?= h($flight['body']) ?></p>
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
      <div class="flash flash--success">Thanks — your flight booking request has been sent. We'll be in touch shortly.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/flight-booking-submit.php')) ?>">
        <?= csrf_field() ?>
        <div class="site-form__row">
          <label for="flight_type">Flight type</label>
          <select id="flight_type" name="flight_type" required>
            <option value="">Select a flight type&hellip;</option>
            <?php foreach ($flightTypes as $slug => $flight): ?>
              <option value="<?= h($flight['label']) ?>" <?= $selectedType === $slug ? 'selected' : '' ?>><?= h($flight['label']) ?></option>
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
          <label for="travel_date">Travel date</label>
          <input type="date" id="travel_date" name="travel_date">
        </div>
        <div class="site-form__row">
          <label for="message">Anything else we should know?</label>
          <textarea id="message" name="message" rows="5" placeholder="Route, number of people, luggage..."></textarea>
        </div>
        <button type="submit" class="btn btn--primary">Request Quote</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
