<?php
declare(strict_types=1);

/**
 * Renders a topographic contour-line motif: concentric rings around one or
 * more peaks, each ring's center drifting slightly from the last (the way
 * real elevation contours do), rather than perfect concentric circles.
 */
function render_contour_svg(array $peaks, int $width, int $height, string $class = ''): string
{
    $colors = ['#e8dcc3', '#c99a3e', '#e8dcc3'];
    $rings = '';

    foreach ($peaks as $peak) {
        [$cx, $cy, $maxR, $ringCount, $seed] = $peak;
        $driftX = $cx;
        $driftY = $cy;

        for ($i = $ringCount; $i >= 1; $i--) {
            $t = $i / $ringCount;
            $r = $maxR * $t;
            $rx = $r * (1 + 0.08 * sin($seed + $i * 1.3));
            $ry = $r * (1 - 0.06 * cos($seed + $i * 0.9));
            $rot = 8 * sin($seed + $i);

            $driftX += 1.6 * sin($seed + $i * 2.1);
            $driftY += 1.3 * cos($seed + $i * 1.7);

            $opacity = 0.18 + 0.5 * (1 - $t);
            $color = $colors[$i % count($colors)];
            $strokeWidth = $i === 1 ? 1.6 : 1;

            $rings .= sprintf(
                '<ellipse cx="%.1f" cy="%.1f" rx="%.1f" ry="%.1f" transform="rotate(%.1f %.1f %.1f)" fill="none" stroke="%s" stroke-width="%.1f" opacity="%.2f"/>',
                $driftX, $driftY, $rx, $ry, $rot, $driftX, $driftY, $color, $strokeWidth, $opacity
            );
        }
    }

    return sprintf(
        '<svg class="%s" viewBox="0 0 %d %d" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">%s</svg>',
        h($class), $width, $height, $rings
    );
}

function sidebar_mark_svg(): string
{
    return render_contour_svg([[15, 17, 13, 4, 0.6]], 30, 30, 'sidebar__mark');
}

function login_field_svg(): string
{
    return render_contour_svg([
        [230, 180, 210, 7, 0.3],
        [560, 420, 260, 8, 2.1],
        [120, 560, 150, 5, 4.4],
    ], 800, 800, 'login-page__contour');
}

function sidebar_field_svg(): string
{
    return render_contour_svg([[190, 190, 170, 6, 1.1]], 260, 260, 'sidebar__contour');
}
