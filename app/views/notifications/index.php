<?php use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Notifications</h4>
    <?php if (!empty($notifications)): ?>
    <form method="post" action="/notifications/read-all">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        <?php if (empty($notifications)): ?>
        <div class="p-4 text-center text-muted">No notifications yet.</div>
        <?php endif; ?>
        <?php foreach ($notifications as $n): ?>
        <div class="list-group-item d-flex justify-content-between align-items-start <?= $n['is_read'] ? '' : 'bg-light' ?>">
            <div>
                <?php $badge = ['info' => 'info', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger'][$n['type']] ?? 'secondary'; ?>
                <span class="badge bg-<?= $badge ?> text-capitalize me-2"><?= htmlspecialchars($n['type']) ?></span>
                <span class="fw-<?= $n['is_read'] ? 'normal' : 'bold' ?>"><?= htmlspecialchars($n['title']) ?></span>
                <div class="text-muted small mt-1"><?= htmlspecialchars($n['message']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($n['created_at']) ?></div>
                <?php if ($n['link']): ?><a href="<?= htmlspecialchars($n['link']) ?>" class="small">View</a><?php endif; ?>
            </div>
            <?php if (!$n['is_read']): ?>
            <form method="post" action="/notifications/<?= (int) $n['id'] ?>/read">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-sm btn-link">Mark read</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
