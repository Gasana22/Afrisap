<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Whitelist of entity types the generic gallery manager is allowed to touch.
const MEDIA_ENTITY_TYPES = [
    'hero_slideshow',
    'destination',
    'destination_activity',
    'tour',
    'tour_overview',
    'tour_hotel',
    'tour_vehicle',
    'tour_flight',
    'tour_activity',
    'activity',
    'experience_destination',
    'experience_destination_activity',
    'experience_tour',
    'experience_tour_overview',
    'experience_tour_activity',
    'experience_tour_accommodation',
    'service_provider',
];

function get_media(string $entityType, int $entityId): array
{
    $stmt = db()->prepare('SELECT * FROM media WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order, id');
    $stmt->execute([$entityType, $entityId]);
    return $stmt->fetchAll();
}

function get_cover_image(string $entityType, int $entityId): ?string
{
    $stmt = db()->prepare('SELECT file_path FROM media WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order, id LIMIT 1');
    $stmt->execute([$entityType, $entityId]);
    $path = $stmt->fetchColumn();
    return $path !== false ? $path : null;
}

function media_count(string $entityType, int $entityId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM media WHERE entity_type = ? AND entity_id = ?');
    $stmt->execute([$entityType, $entityId]);
    return (int) $stmt->fetchColumn();
}

function add_media(string $entityType, int $entityId, string $filePath, ?string $caption): void
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM media WHERE entity_type = ? AND entity_id = ?');
    $stmt->execute([$entityType, $entityId]);
    $nextOrder = (int) $stmt->fetchColumn();

    db()->prepare('INSERT INTO media (entity_type, entity_id, file_path, caption, sort_order) VALUES (?, ?, ?, ?, ?)')
        ->execute([$entityType, $entityId, $filePath, $caption, $nextOrder]);
}

function delete_media(int $mediaId): void
{
    $stmt = db()->prepare('SELECT file_path FROM media WHERE id = ?');
    $stmt->execute([$mediaId]);
    $path = $stmt->fetchColumn();

    db()->prepare('DELETE FROM media WHERE id = ?')->execute([$mediaId]);

    if ($path) {
        $abs = __DIR__ . '/../' . $path;
        if (is_file($abs)) {
            unlink($abs);
        }
    }
}
