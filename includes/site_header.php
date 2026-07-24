<?php
declare(strict_types=1);

require_once __DIR__ . '/site_svg.php';

// Prefixed with nav_ and scoped inside a closure so the mega-menu's own loop
// variables can never clobber same-named variables set by the including page
$render_site_nav = static function () {
    $nav_safari_categories = db()->query("SELECT id, name, slug FROM tour_categories WHERE menu_group = 'safari' ORDER BY sort_order")->fetchAll();
    $nav_trip_categories = db()->query('SELECT id, name, slug, sort_order FROM trip_tour_categories ORDER BY sort_order')->fetchAll();
    $nav_experience_types = db()->query('SELECT name, slug, sort_order FROM experience_types ORDER BY sort_order')->fetchAll();
    $nav_activities = db()->query('SELECT id, name FROM activities ORDER BY name')->fetchAll();

    $nav_operator_count = (int) db()->query('SELECT COUNT(*) FROM tour_operators')->fetchColumn();

    require __DIR__ . '/site_nav.php';
};

$pageTitle = $page_title ?? 'Safarisap - Explore. Experience. Belong.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="Safarisap — East African safari, cultural and adventure tours. Explore. Experience. Belong.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">

<!-- CSS - Using assetUrl() helper -->
<link rel="stylesheet" href="<?= h(assetUrl('css/site.css')) ?>">

</head>
<body class="site">

<div class="utility-bar">
  <div class="wrap">
    <div>Calls: <a href="tel:+256393246926">+256 393 246 926</a> · WhatsApp: <a href="https://wa.me/256775328952">+256 775 328 952</a> · Email: <a href="mailto:info@safarisap.com">info@safarisap.com</a></div>
    <div class="utility-bar__branches">Branches: Kampala, Kigali</div>
  </div>
</div>

<?php $render_site_nav(); ?>