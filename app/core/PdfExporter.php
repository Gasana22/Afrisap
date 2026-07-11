<?php

namespace App\Core;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExporter
{
    /**
     * Streams a simple tabular report as a PDF download and ends the request.
     *
     * @param string[] $headers
     * @param array<int, array<int, string>> $rows
     */
    public static function streamTable(string $title, array $headers, array $rows, string $filename): void
    {
        $html = '<html><head><meta charset="utf-8"><style>
            body { font-family: sans-serif; font-size: 11px; color: #222; }
            h1 { font-size: 16px; margin-bottom: 4px; }
            .generated { color: #777; font-size: 10px; margin-bottom: 14px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
            th { background: #f0f0f0; }
        </style></head><body>';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<div class="generated">Generated ' . htmlspecialchars(date('Y-m-d H:i')) . '</div>';
        $html .= '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        self::stream($html, $filename, 'landscape');
    }

    /**
     * Streams arbitrary HTML (a freeform document, not a table) as a PDF download and ends the request.
     */
    public static function streamHtml(string $html, string $filename, string $orientation = 'portrait'): void
    {
        self::stream($html, $filename, $orientation);
    }

    private static function stream(string $html, string $filename, string $orientation): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
