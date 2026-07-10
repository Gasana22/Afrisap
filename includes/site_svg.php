<?php
declare(strict_types=1);

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
 * up into filled ridgelines), a contour-ringed sun low on the horizon,
 * acacia silhouettes, and a scatter of birds. Pure SVG, no photography.
 */
function render_hero_illustration(): string
{
    $width = 1600;
    $height = 700;

    // Layered misty hill ridges, back to front.
    $layers = [
        ['baseY' => 430, 'amp' => 26, 'freq' => 1.3, 'phase' => 0.4, 'color' => '#3c5c49', 'opacity' => 0.55],
        ['baseY' => 480, 'amp' => 34, 'freq' => 1.7, 'phase' => 2.1, 'color' => '#2c4a3a', 'opacity' => 0.75],
        ['baseY' => 540, 'amp' => 42, 'freq' => 1.1, 'phase' => 4.0, 'color' => '#1f3a2e', 'opacity' => 1],
    ];

    $ridges = '';
    foreach ($layers as $layer) {
        $points = [];
        $steps = 12;
        for ($i = 0; $i <= $steps; $i++) {
            $x = ($width / $steps) * $i;
            $y = $layer['baseY'] + $layer['amp'] * sin($layer['phase'] + $i * $layer['freq']);
            $points[] = [$x, $y];
        }
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

    // Contour-ringed sun, low on the horizon.
    $sun = '<g transform="translate(1180,420)">';
    $radii = [92, 72, 52, 32, 14];
    foreach ($radii as $i => $r) {
        $opacity = 0.9 - $i * 0.12;
        $fill = $i === count($radii) - 1 ? '#e8823c' : 'none';
        $sun .= sprintf('<circle r="%d" fill="%s" stroke="#e8823c" stroke-width="1.4" opacity="%.2f"/>', $r, $fill, $opacity);
    }
    $sun .= '</g>';

    $trees = render_acacia(120, $height - 10, 1.15)
        . render_acacia(310, $height - 4, 0.75)
        . render_acacia(1480, $height - 8, 0.9);

    // Birds: simple chevrons scattered in the sky.
    $birdSpots = [[260, 140], [340, 190], [420, 150], [980, 120], [1040, 160]];
    $birds = '';
    foreach ($birdSpots as $spot) {
        $birds .= sprintf(
            '<path d="M%d,%d q10,-12 20,0 q10,-12 20,0" fill="none" stroke="#f1e9d8" stroke-width="2.2" stroke-linecap="round" opacity="0.75"/>',
            $spot[0], $spot[1]
        );
    }

    return sprintf(
        '<svg class="hero__illustration" viewBox="0 0 %d %d" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">%s%s%s%s</svg>',
        $width, $height, $sun, $ridges, $birds, $trees
    );
}
