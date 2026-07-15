<?php
/**
 * In-app notification helpers.
 */

function notify(int $organizationId, ?int $userId, string $type, string $title, string $message, ?string $link = null): int
{
    return db_insert('notifications', [
        'organization_id' => $organizationId,
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $link,
    ]);
}

function unread_notification_count(int $organizationId, ?int $userId): int
{
    if (!$userId) {
        return 0;
    }

    return (int) db_value(
        'SELECT COUNT(*) FROM notifications WHERE organization_id = :org_id AND (user_id = :user_id OR user_id IS NULL) AND is_read = 0',
        ['org_id' => $organizationId, 'user_id' => $userId]
    );
}

function recent_notifications(int $organizationId, ?int $userId, int $limit = 8): array
{
    if (!$userId) {
        return [];
    }

    return db_all(
        'SELECT * FROM notifications
         WHERE organization_id = :org_id AND (user_id = :user_id OR user_id IS NULL)
         ORDER BY created_at DESC LIMIT ' . (int) $limit,
        ['org_id' => $organizationId, 'user_id' => $userId]
    );
}

function mark_notification_read(int $id, int $organizationId): void
{
    db_update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'id = :id AND organization_id = :org_id', [
        'id' => $id,
        'org_id' => $organizationId,
    ]);
}
