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

/**
 * Horizontal stage-progress bar for the public traceability pages, driven
 * by TRACE_JOURNEY_STAGES (config/constants.php). Each recorded
 * product_journey row is matched against the canonical list by a
 * case-insensitive, trimmed comparison of its "stage" value; canonical
 * stages with no match are shown as upcoming (numbered, not filled).
 */
function render_journey_progress(array $journey): void
{
    $byStage = [];
    foreach ($journey as $row) {
        $key = strtolower(trim((string) $row['stage']));
        if (!isset($byStage[$key])) {
            $byStage[$key] = $row;
        }
    }

    $stages = TRACE_JOURNEY_STAGES;
    $last = count($stages) - 1;

    echo '<div class="stage-progress">';
    foreach ($stages as $i => $stage) {
        $match = $byStage[$stage] ?? null;
        $done = $match !== null;

        echo '<div class="stage-step' . ($done ? ' done' : '') . '">';
        echo '<div class="stage-dot">' . ($done ? '<i class="bi bi-check-lg"></i>' : (string) ($i + 1)) . '</div>';
        echo '<div class="stage-label">' . e(humanize($stage)) . '</div>';
        if ($done && !empty($match['start_date'])) {
            echo '<div class="small text-muted">' . e(format_date($match['start_date'])) . '</div>';
        }
        echo '</div>';

        if ($i < $last) {
            echo '<div class="stage-line' . ($done ? ' done' : '') . '"></div>';
        }
    }
    echo '</div>';
}
