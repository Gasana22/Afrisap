<?php
/**
 * Closing structural markup + JS includes.
 */

function render_footer(array $opts = []): void
{
    $context = $opts['context'] ?? 'public';
    $extraJs = $opts['js'] ?? [];

    $baseJsByContext = [
        'public' => 'public.js',
        'admin' => 'admin.js',
        'org-admin' => 'dashboard.js',
        'worker' => 'worker.js',
        'auth' => 'public.js',
    ];
    $baseJs = $baseJsByContext[$context] ?? 'public.js';

    if ($context === 'public') {
        render_public_footer_nav();
    }

    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="' . asset_url('js/api.js') . '"></script>';
    echo '<script src="' . asset_url("js/$baseJs") . '"></script>';

    foreach ($extraJs as $js) {
        if ($js === 'charts') {
            echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>';
        }
        if ($js === 'map') {
            echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
            echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
        }
        echo '<script src="' . asset_url("js/$js.js") . '"></script>';
    }

    echo '</body></html>';
}
