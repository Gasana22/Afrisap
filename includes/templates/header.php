<?php
/**
 * Page shell - <head> + opening structural markup.
 *
 * Usage:
 *   render_header(['title' => 'Dashboard', 'context' => 'org-admin', 'css' => ['dashboard']]);
 *   ... page content ...
 *   render_footer(['context' => 'org-admin', 'js' => ['dashboard', 'charts']]);
 */

function render_header(array $opts = []): void
{
    $title = $opts['title'] ?? app_config()['app']['name'];
    $context = $opts['context'] ?? 'public';
    $extraCss = $opts['css'] ?? [];
    $bodyClass = $opts['body_class'] ?? '';

    $baseCssByContext = [
        'public' => 'public.css',
        'admin' => 'admin.css',
        'org-admin' => 'dashboard.css',
        'worker' => 'worker.css',
        'auth' => 'public.css',
    ];
    $baseCss = $baseCssByContext[$context] ?? 'public.css';

    echo '<!DOCTYPE html><html lang="en" data-context="' . e($context) . '"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<title>' . e($title) . ' | ' . e(app_config()['app']['name']) . '</title>';
    echo '<link rel="icon" href="data:,">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">';
    echo '<link rel="stylesheet" href="' . asset_url('css/tokens.css') . '">';
    echo '<link rel="stylesheet" href="' . asset_url('css/' . $baseCss) . '">';
    foreach ($extraCss as $css) {
        echo '<link rel="stylesheet" href="' . asset_url("css/$css.css") . '">';
    }
    echo '<link rel="stylesheet" href="' . asset_url('css/responsive.css') . '">';
    echo '</head><body class="ctx-' . e($context) . ' ' . e($bodyClass) . '">';
}
