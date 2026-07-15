<?php
declare(strict_types=1);

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;

const UPLOAD_ALLOWED_IMAGE_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

const UPLOAD_ALLOWED_DOCUMENT_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];

/**
 * Moves an uploaded file into /uploads/{Y}/{m}/ and returns the path
 * relative to the project root (e.g. "uploads/2026/07/64f...a1.jpg"),
 * or null if no file was submitted for this field.
 *
 * @param array<string,string> $allowedMime mime type => file extension
 * @throws RuntimeException on an invalid or oversized file.
 */
function handle_upload(string $fieldName, array $allowedMime, string $tooLargeMessage, string $wrongTypeMessage): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file failed to upload. Try again.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException($tooLargeMessage);
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowedMime[$mime])) {
        throw new RuntimeException($wrongTypeMessage);
    }

    $ext = $allowedMime[$mime];
    $subdir = 'uploads/' . date('Y') . '/' . date('m');
    $absDir = __DIR__ . '/../' . $subdir;

    if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        throw new RuntimeException('Could not create the upload directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $relPath = $subdir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $relPath)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }

    return $relPath;
}

function handle_image_upload(string $fieldName): ?string
{
    return handle_upload($fieldName, UPLOAD_ALLOWED_IMAGE_MIME, 'That image is too large (5MB max).', 'Only JPG, PNG or WEBP images are allowed.');
}

function handle_document_upload(string $fieldName): ?string
{
    return handle_upload($fieldName, UPLOAD_ALLOWED_DOCUMENT_MIME, 'That file is too large (5MB max).', 'Only JPG, PNG, WEBP or PDF files are allowed.');
}
