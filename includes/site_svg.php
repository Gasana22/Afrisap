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
