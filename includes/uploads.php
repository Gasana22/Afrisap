<?php
/**
 * File upload handling - tenant-isolated under storage/uploads/{organization_id}/{category}/.
 */

function handle_upload(array $file, int $organizationId, string $category): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $config = app_config()['uploads'];

    if ($file['size'] > $config['max_size']) {
        throw new RuntimeException('File exceeds the maximum upload size.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowed_types'], true)) {
        throw new RuntimeException('File type not allowed.');
    }

    $dir = UPLOADS_PATH . "/$organizationId/$category";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = "$dir/$filename";

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to store uploaded file.');
    }

    return "$organizationId/$category/$filename";
}

function uploaded_file_url(?string $relativePath): ?string
{
    if (!$relativePath) {
        return null;
    }

    return base_url('storage/uploads/' . $relativePath);
}
