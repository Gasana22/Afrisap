<?php

namespace App\Models;

use App\Core\Database;
use App\Core\QrGenerator;

class TraceQrCode
{
    public static function forBatch(int $traceBatchId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM trace_qr_codes WHERE trace_batch_id = :id');
        $stmt->execute(['id' => $traceBatchId]);
        return $stmt->fetch() ?: null;
    }

    public static function generate(int $traceBatchId, string $publicBaseUrl): array
    {
        $existing = self::forBatch($traceBatchId);
        if ($existing) {
            return $existing;
        }

        $token = bin2hex(random_bytes(16));
        $config = require __DIR__ . '/../config/config.php';
        $relativePath = "uploads/qrcodes/{$token}.png";
        $absolutePath = $config['uploads']['path'] . "/qrcodes/{$token}.png";

        QrGenerator::generate(rtrim($publicBaseUrl, '/') . "/trace/{$token}", $absolutePath);

        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO trace_qr_codes (trace_batch_id, public_token, qr_image_path) VALUES (:batch_id, :token, :path)');
        $stmt->execute(['batch_id' => $traceBatchId, 'token' => $token, 'path' => $relativePath]);

        return self::forBatch($traceBatchId);
    }
}
