<?php
declare(strict_types=1);

/**
 * Small line-art glyphs (20x20, stroke=currentColor) used next to every nav
 * and mega-menu label, per client request that every menu item -- including
 * every dropdown item -- carry an image. There's no per-category/activity
 * photography or upload field in the schema yet, so these are drawn glyphs
 * rather than photos: consistent with the rest of the public site, which is
 * pure SVG illustration throughout, no photography.
 */
function render_nav_glyph(string $name): string
{
    static $glyphs = [
        'compass' => '<circle cx="10" cy="10" r="7.5"/><path d="M12.8 7.2 9.3 9.3 7.2 12.8l3.5-2.1 2.1-3.5z" stroke-linejoin="round"/>',
        'binoculars' => '<circle cx="6.5" cy="13.5" r="3"/><circle cx="13.5" cy="13.5" r="3"/><path d="M8 7h4M7 7 6.5 10.7M13 7l.5 3.7M8.5 5.5h3l.5 1.5h-4z"/>',
        'mountain' => '<path d="M2 16 7.5 6l3 5 1.5-2L18 16z" stroke-linejoin="round"/>',
        'bird' => '<path d="M3 12q2.5-4 5-1.5Q10.5 8 13 9.5q2.2-2.8 4-1.2-2 .8-2.6 2.7-1-1-2.4-.5-.8 1.6-3 1.6-1.8 0-3-1.4Z"/>',
        'boat' => '<path d="M3 12h14l-1.5 4h-11z"/><path d="M10 12V4M10 4l4 3M10 6l-3.5 2"/>',
        'plane' => '<path d="M2 11l16-5-3 5 3 5-16-5Z" stroke-linejoin="round"/>',
        'tent' => '<path d="M10 4 3 16h14z" stroke-linejoin="round"/><path d="M10 4v12"/>',
        'palm' => '<path d="M10 17V9"/><path d="M10 9c0-3-2-4.5-4.5-5C6.5 6.5 8 8 10 9M10 9c0-3 2-4.5 4.5-5C13.5 6.5 12 8 10 9M10 9c-2-1.5-4.5-1.5-6 0M10 9c2-1.5 4.5-1.5 6 0"/>',
        'bag' => '<rect x="3.5" y="7" width="13" height="10" rx="1.5"/><path d="M7 7V5.5a3 3 0 0 1 6 0V7"/>',
        'building' => '<rect x="4" y="3" width="8" height="14" rx="1"/><path d="M12 8h4v9h-4M6.5 6h1M9.5 6h1M6.5 9h1M9.5 9h1M6.5 12h1M9.5 12h1"/>',
        'book' => '<path d="M10 6c-1.5-1.2-3.5-1.5-6-1v10.5c2.5-.5 4.5-.2 6 1 1.5-1.2 3.5-1.5 6-1V5c-2.5-.5-4.5-.2-6 1Z" stroke-linejoin="round"/><path d="M10 6v10.5"/>',
        'mask' => '<path d="M3 8c1 3 2 6 7 6s6-3 7-6" /><circle cx="7" cy="8.5" r="1"/><circle cx="13" cy="8.5" r="1"/><path d="M3 8c2-3.5 5-5 7-5s5 1.5 7 5"/>',
        'leaf' => '<path d="M4 16c0-6.5 4.5-11 12-11 0 7.5-4.5 11-12 11Z" stroke-linejoin="round"/><path d="M4 16 12.5 7.5"/>',
        'factory' => '<path d="M3 17V9l4 2.5V9l4 2.5V9l4 2.5V17Z" stroke-linejoin="round"/><path d="M3 17h13"/><path d="M14 9V6h2v3"/>',
        'ball' => '<circle cx="10" cy="10" r="7.5"/><path d="M10 2.5v15M2.5 10h15M4.5 4.5l11 11M15.5 4.5l-11 11"/>',
        'users' => '<circle cx="7.5" cy="7.5" r="2.5"/><circle cx="14" cy="8.5" r="2"/><path d="M2.5 16.5c.5-3 2.3-4.5 5-4.5s4.5 1.5 5 4.5M13 12.3c2 .2 3.2 1.5 3.6 3.7"/>',
        'tag' => '<path d="M10 3h5.5L17 4.5V10l-8 8-6.5-6.5Z" stroke-linejoin="round"/><circle cx="13" cy="6.5" r="1.2"/>',
        'briefcase' => '<rect x="2.5" y="7" width="15" height="9.5" rx="1.5"/><path d="M7 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 13 5v2M2.5 11h15"/>',
        'doc' => '<path d="M5 2.5h7l3 3v12h-10Z" stroke-linejoin="round"/><path d="M12 2.5V6h3M7 10h6M7 13h6"/>',
        'pin' => '<path d="M10 17.5S4 12 4 7.5a6 6 0 1 1 12 0c0 4.5-6 10-6 10Z" stroke-linejoin="round"/><circle cx="10" cy="7.5" r="2"/>',
        'mail' => '<rect x="2.5" y="4.5" width="15" height="11" rx="1.5"/><path d="M2.5 5.5 10 11l7.5-5.5"/>',
        'image' => '<rect x="2.5" y="3.5" width="15" height="13" rx="1.5"/><circle cx="7" cy="8" r="1.4"/><path d="M4 15l4.5-4.5 3 3 2-2 4.5 4.5"/>',
        'calendar' => '<rect x="2.5" y="4" width="15" height="13.5" rx="1.5"/><path d="M2.5 8h15M6.5 2.5v3M13.5 2.5v3"/>',
        'sliders' => '<path d="M4 5h12M4 10h12M4 15h12"/><circle cx="7" cy="5" r="1.6"/><circle cx="13" cy="10" r="1.6"/><circle cx="9" cy="15" r="1.6"/>',
        'video' => '<rect x="2.5" y="5.5" width="10" height="9" rx="1.3"/><path d="m12.5 8.5 5-2.3v7.6l-5-2.3Z" stroke-linejoin="round"/>',
        'paw' => '<circle cx="6" cy="6.5" r="1.4"/><circle cx="10" cy="5" r="1.4"/><circle cx="14" cy="6.5" r="1.4"/><path d="M10 15c-2.6 0-4-1.3-4-3s1.6-3 4-3 4 1.3 4 3-1.4 3-4 3Z"/>',
    ];

    $body = $glyphs[$name] ?? $glyphs['compass'];

    return '<svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">' . $body . '</svg>';
}

/**
 * Picks a glyph name for a nav/mega-menu label using keyword matching, so
 * DB-driven items (tour categories, experience types, activities) get a
 * sensible icon without needing a dedicated icon column on every table.
 */
function nav_icon_for(string $label): string
{
    $label = strtolower($label);

    $map = [
        'gorilla' => 'paw', 'chimp' => 'paw', 'wildlife' => 'binoculars', 'game' => 'binoculars',
        'bird' => 'bird', 'combined' => 'compass', 'mixed' => 'compass', 'adventure' => 'mountain',
        'island' => 'palm', 'beach' => 'palm', 'camp' => 'tent', 'fly' => 'plane', 'flying' => 'plane',
        'shop' => 'bag', 'boat' => 'boat', 'cruise' => 'boat', 'city' => 'building', 'school' => 'book',
        'cultural' => 'mask', 'tribe' => 'mask', 'farm' => 'leaf', 'ghetto' => 'building',
        'manufactur' => 'factory', 'factory' => 'factory', 'sport' => 'ball',
        'skydiv' => 'plane', 'raft' => 'boat', 'horse' => 'paw', 'quote' => 'tag',
        'agent' => 'users', 'career' => 'briefcase', 'blog' => 'doc', 'travel tip' => 'pin',
        'contact' => 'mail', 'gallery' => 'image', 'about' => 'compass',
        'scheduled' => 'calendar', 'create your own' => 'sliders', 'virtual' => 'video',
        'operator' => 'briefcase',
    ];

    foreach ($map as $needle => $glyph) {
        if (str_contains($label, $needle)) {
            return $glyph;
        }
    }

    return 'compass';
}

/** Flat-top acacia silhouette: trunk + a wide, flat canopy (the iconic savanna umbrella shape). */
function render_acacia(int $cx, int $baseY, float $scale): string
{
    $trunkW = 6 * $scale;
    $trunkH = 68 * $scale;
    $canopyRx = 100 * $scale;
    $canopyRy = 20 * $scale;
    $canopyCy = $trunkH + $canopyRy; // canopy center, measured upward from baseY

    $group = sprintf('<g transform="translate(%d,%d)">', $cx, $baseY);
    $group .= sprintf('<rect x="%.1f" y="-%.1f" width="%.1f" height="%.1f" fill="#14201b"/>', -$trunkW / 2, $trunkH, $trunkW, $trunkH);
    $group .= sprintf('<ellipse cx="0" cy="-%.1f" rx="%.1f" ry="%.1f" fill="#14201b"/>', $canopyCy, $canopyRx, $canopyRy);
    $group .= '</g>';

    return $group;
}

/**
 * Renders the hero's signature illustration: a layered misty-hills skyline
 * (the same topographic language as the admin panel's contour mark, scaled
 * up into filled ridgelines) with open contour-line strokes threaded through
 * them like an elevation map, a waypoint marker low on the horizon, acacia
 * silhouettes, and a scatter of birds. Composition is weighted to the right
 * two-thirds so the headline (set left, not centered) reads over calmer sky.
 * Pure SVG, no photography.
 */
function render_hero_illustration(): string
{
    $width = 1600;
    $height = 700;

    // Layered misty hill ridges, back to front. Deliberately asymmetric --
    // low and calm on the left third where the headline sits, rising into
    // the right two-thirds.
    $layers = [
        ['baseY' => 460, 'amp' => 30, 'freq' => 1.1, 'phase' => 1.1, 'color' => '#3c5c49', 'opacity' => 0.5],
        ['baseY' => 500, 'amp' => 40, 'freq' => 1.4, 'phase' => 2.6, 'color' => '#2c4a3a', 'opacity' => 0.7],
        ['baseY' => 560, 'amp' => 46, 'freq' => 0.9, 'phase' => 4.4, 'color' => '#1f3a2e', 'opacity' => 1],
    ];

    $ridgePoints = [];
    $ridges = '';
    foreach ($layers as $li => $layer) {
        $points = [];
        $steps = 14;
        for ($i = 0; $i <= $steps; $i++) {
            $x = ($width / $steps) * $i;
            $rise = $x < $width * 0.32 ? 0.35 : 1;
            $y = $layer['baseY'] + $layer['amp'] * $rise * sin($layer['phase'] + $i * $layer['freq']);
            $points[] = [$x, $y];
        }
        $ridgePoints[$li] = $points;

        $d = 'M0,' . $height . ' L' . $points[0][0] . ',' . $points[0][1];
        for ($i = 1; $i < count($points); $i++) {
            $prev = $points[$i - 1];
            $curr = $points[$i];
            $midX = ($prev[0] + $curr[0]) / 2;
            $d .= sprintf(' Q%.1f,%.1f %.1f,%.1f', $prev[0], $prev[1], $midX, ($prev[1] + $curr[1]) / 2);
        }
        $d .= sprintf(' L%d,%.1f L%d,%d Z', $width, $points[count($points) - 1][1], $width, $height);

        $ridges .= sprintf('<path d="%s" fill="%s" opacity="%.2f"/>', $d, $layer['color'], $layer['opacity']);
    }

    // Open elevation-contour strokes, offset above each filled ridge --
    // the "map reading" of the same skyline, reinforcing the brand's
    // topographic mark rather than leaving the hills purely decorative.
    $contours = '';
    foreach ($ridgePoints as $li => $points) {
        foreach ([18, 34] as $offset) {
            $d = sprintf('M%.1f,%.1f', $points[0][0], $points[0][1] - $offset);
            for ($i = 1; $i < count($points); $i++) {
                $prev = $points[$i - 1];
                $curr = $points[$i];
                $midX = ($prev[0] + $curr[0]) / 2;
                $midY = ($prev[1] + $curr[1]) / 2 - $offset;
                $d .= sprintf(' Q%.1f,%.1f %.1f,%.1f', $prev[0], $prev[1] - $offset, $midX, $midY);
            }
            $contours .= sprintf('<path d="%s" fill="none" stroke="#a97f2e" stroke-width="1" opacity="%.2f"/>', $d, 0.16 + $li * 0.05);
        }
    }

    // Waypoint marker low on the horizon -- concentric rings around a
    // pinned point, echoing the admin panel's contour mark, with a short
    // tick grounding it like a map pin rather than a floating sun icon.
    $sun = '<g transform="translate(1180,440)">';
    $radii = [92, 72, 52, 32];
    foreach ($radii as $i => $r) {
        $opacity = 0.55 - $i * 0.1;
        $sun .= sprintf('<circle r="%d" fill="none" stroke="#e8823c" stroke-width="1.2" opacity="%.2f"/>', $r, $opacity);
    }
    $sun .= '<circle r="6" fill="#e8823c"/>';
    $sun .= '<line x1="0" y1="6" x2="0" y2="26" stroke="#e8823c" stroke-width="1.2" opacity="0.6"/>';
    $sun .= '</g>';

    $trees = render_acacia(1140, $height - 6, 0.85)
        . render_acacia(1360, $height - 12, 1.1)
        . render_acacia(1500, $height - 4, 0.7);

    // Birds: simple chevrons scattered in the sky, right two-thirds only.
    $birdSpots = [[900, 130], [960, 175], [1300, 110], [1360, 150]];
    $birds = '';
    foreach ($birdSpots as $spot) {
        $birds .= sprintf(
            '<path d="M%d,%d q10,-12 20,0 q10,-12 20,0" fill="none" stroke="#f1e9d8" stroke-width="2.2" stroke-linecap="round" opacity="0.7"/>',
            $spot[0], $spot[1]
        );
    }

    return sprintf(
        '<svg class="hero__illustration" viewBox="0 0 %d %d" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">%s%s%s%s%s</svg>',
        $width, $height, $sun, $ridges, $contours, $birds, $trees
    );
}
