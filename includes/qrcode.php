<?php
/**
 * QR code generation for traceability batches, via endroid/qr-code.
 */

require_once ROOT_PATH . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Generates a QR code PNG for the given data and stores it under
 * storage/uploads/{organization_id}/qr-codes/, returning the relative path.
 */
function generate_qr_code_file(string $data, int $organizationId, string $filenameHint): string
{
    $dir = UPLOADS_PATH . "/$organizationId/qr-codes";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($data)
        ->size(300)
        ->margin(10)
        ->build();

    $filename = sanitize_filename($filenameHint) . '.png';
    $result->saveToFile("$dir/$filename");

    return "$organizationId/qr-codes/$filename";
}

function batch_trace_url(array $organization, string $batchId): string
{
    return base_url('org/' . $organization['slug'] . '/trace.php?batch=' . urlencode($batchId));
}
