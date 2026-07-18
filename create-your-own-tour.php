<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

$destinations = db()->query('SELECT d.id, d.name, c.name AS country_name FROM destinations d JOIN countries c ON c.id = d.country_id ORDER BY c.name, d.name')->fetchAll();
$activities = db()->query('SELECT id, name FROM activities ORDER BY name')->fetchAll();
$experienceTypes = db()->query('SELECT id, name FROM experience_types ORDER BY name')->fetchAll();

$page_title = 'Create Your Own Tour - Safarisap';
require __DIR__ . '/includes/site_header.php';
?>

<header class="page-header">
  <div class="wrap">
    <p class="page-header__eyebrow">Specialised Tours</p>
    <h1 class="page-header__title">Create Your Own Tour</h1>
    <p class="page-header__lead">Tell us the trip you're picturing -- destinations, budget, activities, a touch of experiential culture, how many days, how many people -- and a consultant will build it with you.</p>
  </div>
</header>

<section class="section">
  <div class="wrap" style="max-width:720px;">
    <?php if ($sent): ?>
      <div class="flash flash--success">Thanks — we've got your request. A consultant will reach out to build this tour with you.</div>
    <?php else: ?>
      <form class="site-form" method="post" action="<?= h(url('/create-your-own-tour-submit.php')) ?>">
        <?= csrf_field() ?>

        <div class="site-form__row">
          <label for="tour_name">What should we call this trip? (optional)</label>
          <input type="text" id="tour_name" name="tour_name" placeholder="e.g. Our Uganda &amp; Rwanda honeymoon">
        </div>

        <div class="site-form__row">
          <label for="pax">Number of people</label>
          <input type="number" id="pax" name="pax" min="1" value="2" required>
        </div>

        <div class="site-form__row">
          <label for="days">Number of days</label>
          <input type="number" id="days" name="days" min="1" value="7" required>
        </div>

        <div class="site-form__row">
          <label for="budget_type">Budget</label>
          <select id="budget_type" name="budget_type">
            <option value="Luxury">Luxury</option>
            <option value="Mid-Range" selected>Mid-Range</option>
            <option value="Budget">Budget</option>
          </select>
        </div>

        <div class="site-form__row">
          <label>Destinations you're interested in (optional)</label>
          <?php if (!$destinations): ?>
            <p class="check-group__empty">Destinations are still being added -- describe where you'd like to go in the notes below.</p>
          <?php else: ?>
            <div class="check-group">
              <?php foreach ($destinations as $dest): ?>
                <label class="check-row"><input type="checkbox" name="destination_ids[]" value="<?= (int) $dest['id'] ?>"> <?= h($dest['name']) ?> <span style="opacity:.55;">(<?= h($dest['country_name']) ?>)</span></label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="site-form__row">
          <label>Activities you'd like to include (optional)</label>
          <?php if (!$activities): ?>
            <p class="check-group__empty">Activities are still being added -- describe what you'd like to do in the notes below.</p>
          <?php else: ?>
            <div class="check-group">
              <?php foreach ($activities as $activity): ?>
                <label class="check-row"><input type="checkbox" name="activity_ids[]" value="<?= (int) $activity['id'] ?>"> <?= h($activity['name']) ?></label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="site-form__row">
          <label>Mix in some experiential culture? (optional)</label>
          <?php if (!$experienceTypes): ?>
            <p class="check-group__empty">Experience types are still being added.</p>
          <?php else: ?>
            <div class="check-group">
              <?php foreach ($experienceTypes as $type): ?>
                <label class="check-row"><input type="checkbox" name="experience_type_ids[]" value="<?= (int) $type['id'] ?>"> <?= h($type['name']) ?></label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="site-form__row">
          <label for="notes">Anything else we should know?</label>
          <textarea id="notes" name="notes" rows="5" placeholder="Dates, must-sees, special occasions, dietary needs..."></textarea>
        </div>

        <p class="site-form__section-title">Your contact details</p>

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

        <button type="submit" class="btn btn--primary">Submit My Tour Request</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
