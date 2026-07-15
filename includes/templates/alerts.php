<?php
/**
 * Flash alert rendering - call render_alerts() right after <body> opens
 * (or at the top of <main>) on any page.
 */

function render_alerts(): void
{
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        $message = session_flash($type);
        if (!$message) {
            continue;
        }

        $bootstrapType = $type === 'error' ? 'danger' : $type;
        echo '<div class="alert alert-' . e($bootstrapType) . ' alert-dismissible fade show" role="alert">'
            . e($message)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            . '</div>';
    }

    if (!empty($GLOBALS['_page_errors'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="mb-0">';
        foreach ($GLOBALS['_page_errors'] as $error) {
            echo '<li>' . e($error) . '</li>';
        }
        echo '</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
