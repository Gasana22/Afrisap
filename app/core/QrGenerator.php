<?php

namespace App\Core;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrGenerator
{
    public static function generate(string $data, string $outputPath): void
    {
        $qrCode = QrCode::create($data)
            ->setSize(320)
            ->setMargin(12);

        $result = (new PngWriter())->write($qrCode);

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $result->saveToFile($outputPath);
    }
}
