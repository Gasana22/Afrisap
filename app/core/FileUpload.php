<?php

namespace App\Core;

class FileUpload
{
    /**
     * Moves an uploaded image into public/uploads/{subdir}/ and returns the
     * web-relative path (e.g. "uploads/activities/2026/07/xyz.jpg"), or null
     * if no file was uploaded.
     */
    public static function storeImage(string $fieldName, string $subdir): ?string
    {
        if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES[$fieldName];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed.');
        }

        $config = require __DIR__ . '/../config/config.php';
        if ($file['size'] > $config['uploads']['max_size']) {
            throw new \RuntimeException('File is too large.');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $config['uploads']['allowed_mimes'], true)) {
            throw new \RuntimeException('Unsupported file type.');
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };

        $relativeDir = "uploads/{$subdir}/" . date('Y/m');
        $absoluteDir = $config['uploads']['path'] . '/' . $subdir . '/' . date('Y/m');
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $absoluteDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Could not save uploaded file.');
        }

        return "{$relativeDir}/{$filename}";
    }
}
