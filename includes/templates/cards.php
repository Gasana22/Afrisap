<?php
/**
 * Reusable card components shared by admin/org-admin dashboards.
 */

function render_stat_card(string $label, string $value, ?string $change = null, string $icon = 'bi-graph-up', string $accent = 'primary'): void
{
    $changeHtml = '';
    if ($change !== null) {
        $isPositive = !str_starts_with($change, '-');
        $changeHtml = '<span class="stat-change ' . ($isPositive ? 'text-success' : 'text-danger') . '">'
            . '<i class="bi ' . ($isPositive ? 'bi-arrow-up-short' : 'bi-arrow-down-short') . '"></i> '
            . e($change) . '</span>';
    }

    echo <<<HTML
    <div class="stat-card stat-card-{$accent}">
        <div class="stat-card-icon"><i class="bi {$icon}"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value">{$value}</div>
            <div class="stat-card-label">{$label}</div>
            {$changeHtml}
        </div>
    </div>
    HTML;
}

function render_empty_state(string $message, string $icon = 'bi-inbox'): void
{
    echo '<div class="empty-state text-center py-5 text-muted">'
        . '<i class="bi ' . e($icon) . ' display-4 d-block mb-2"></i>'
        . e($message)
        . '</div>';
}

function render_status_badge(string $status): void
{
    echo '<span class="badge ' . status_badge_class($status) . '">' . e(humanize($status)) . '</span>';
}
