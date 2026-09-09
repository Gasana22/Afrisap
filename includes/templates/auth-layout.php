<?php
/**
 * Two-column auth page shell: a branded panel (icon, headline, feature
 * pills) on one side, the actual form card on the other. Used by every
 * auth entry point (login, register, forgot-password, worker login) so
 * the brand markup lives in one place instead of duplicated per page.
 *
 * Usage:
 *   render_auth_layout_start([
 *       'headline' => 'Welcome back to your farm.',
 *       'subtitle' => 'Sign in to check today's tasks...',
 *       'features' => ['Track every crop cycle from seed to sale', ...],
 *   ]);
 *   ... form markup ...
 *   render_auth_layout_end();
 */

function render_auth_layout_start(array $opts = []): void
{
    $headline = $opts['headline'] ?? '';
    $subtitle = $opts['subtitle'] ?? '';
    $features = $opts['features'] ?? [];
    $brandLabel = $opts['brand_label'] ?? app_config()['app']['name'];
    $brandIcon = $opts['brand_icon'] ?? 'bi-flower1';
    $brandHref = $opts['brand_href'] ?? base_url('public/index.php');

    echo '<div class="auth-page">';
    echo '<div class="auth-split">';

    echo '<div class="auth-panel d-none d-lg-flex">';
    echo '<a href="' . e($brandHref) . '" class="auth-brand"><span class="brand-icon"><i class="bi ' . e($brandIcon) . '"></i></span><span class="brand-text">' . e($brandLabel) . '</span></a>';
    if ($headline !== '') {
        echo '<h2 class="font-display">' . e($headline) . '</h2>';
    }
    if ($subtitle !== '') {
        echo '<p class="auth-panel-subtitle">' . e($subtitle) . '</p>';
    }
    foreach ($features as $feature) {
        echo '<div class="feature-pill"><i class="bi bi-check-circle-fill"></i> ' . e($feature) . '</div>';
    }
    echo '</div>';

    echo '<div class="auth-form-side">';
    echo '<a href="' . e($brandHref) . '" class="auth-brand d-lg-none"><span class="brand-icon"><i class="bi ' . e($brandIcon) . '"></i></span><span class="brand-text">' . e($brandLabel) . '</span></a>';
    echo '<div class="auth-card">';
}

function render_auth_layout_end(): void
{
    echo '</div>'; // .auth-card
    echo '</div>'; // .auth-form-side
    echo '</div>'; // .auth-split
    echo '</div>'; // .auth-page
}
