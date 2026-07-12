<?php
/**
 * The "provenance trail" — Planted / Growing / Harvested / Verified — the
 * platform's signature visual, since traceability (a real journey from a
 * specific plot to a specific buyer) is what actually sets this product
 * apart from a generic farm spreadsheet. Reused on the homepage hero and
 * the auth brand panel. Pass $trailActive (1-4) to light up that many nodes;
 * omit it to render all four inactive (auth panel — decorative only).
 */
$trailActive = $trailActive ?? 0;
$trailStages = [
    ['label' => 'Planted', 'icon' => '<path d="M12 20V11"/><path d="M12 11c0-3 2-5 5-5 0 3-2 5-5 5z"/><path d="M12 11c0-3-2-5-5-5 0 3 2 5 5 5z"/>'],
    ['label' => 'Growing', 'icon' => '<path d="M12 3c-4.5 4-4.5 11 0 15 4.5-4 4.5-11 0-15z"/><path d="M12 18v3"/>'],
    ['label' => 'Harvested', 'icon' => '<path d="M4 8l8-4 8 4-8 4-8-4z"/><path d="M4 8v7l8 4 8-4V8"/><path d="M12 12v7"/>'],
    ['label' => 'Verified', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/>'],
];
$fillPct = $trailActive > 0 ? round((($trailActive - 1) / 3) * 100) : 0;
?>
<div class="trail" data-trail data-fill="<?= $fillPct ?>">
    <div class="trail-line"></div>
    <div class="trail-line-fill" data-trail-fill></div>
    <?php foreach ($trailStages as $i => $stage): ?>
        <div class="trail-node<?= ($i + 1) <= $trailActive ? ' is-active' : '' ?>">
            <div class="trail-dot">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $stage['icon'] ?></svg>
            </div>
            <div class="trail-node-label"><?= e($stage['label']) ?></div>
        </div>
    <?php endforeach; ?>
</div>
