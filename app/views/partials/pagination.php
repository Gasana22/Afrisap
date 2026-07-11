<?php
/** @var int $page */
/** @var int $totalPages */
/** @var string $baseUrl */
if ($totalPages <= 1) {
    return;
}
$windowStart = max(1, $page - 3);
$windowEnd = min($totalPages, $page + 3);
$sep = str_contains($baseUrl, '?') ? '&' : '?';
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($baseUrl . $sep . 'page=' . max(1, $page - 1)) ?>">Prev</a>
        </li>
        <?php if ($windowStart > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($baseUrl . $sep . 'page=1') ?>">1</a></li>
        <?php if ($windowStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($baseUrl . $sep . 'page=' . $p) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($windowEnd < $totalPages): ?>
        <?php if ($windowEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($baseUrl . $sep . 'page=' . $totalPages) ?>"><?= $totalPages ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($baseUrl . $sep . 'page=' . min($totalPages, $page + 1)) ?>">Next</a>
        </li>
    </ul>
</nav>
