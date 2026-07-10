<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$page_title = 'Dashboard';
$page_eyebrow = 'Overview';
$active_nav = 'dashboard';

$stats = [
    'Countries' => (int) db()->query('SELECT COUNT(*) FROM countries')->fetchColumn(),
    'Tour categories' => (int) db()->query('SELECT COUNT(*) FROM tour_categories')->fetchColumn(),
    'Destinations' => (int) db()->query('SELECT COUNT(*) FROM destinations')->fetchColumn(),
    'Published tours' => (int) db()->query("SELECT COUNT(*) FROM tours WHERE status = 'published'")->fetchColumn(),
];

require __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
  <?php foreach ($stats as $label => $value): ?>
    <div class="stat-tile">
      <div class="stat-tile__label"><?= h($label) ?></div>
      <div class="stat-tile__value"><?= $value ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Getting started</div>
  </div>
  <div class="panel__body">
    <p style="margin:0 0 10px;">Content builds in order: enter <strong>Countries</strong>, then <strong>Tour Categories</strong>, then <strong>Destinations</strong> (which belong to a country). Tours, Activities and Experiential content build on top of these.</p>
    <p style="margin:0; opacity:0.7;">Signed in as <?= h(current_admin()['name']) ?>.</p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
