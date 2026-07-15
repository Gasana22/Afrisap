<?php
/**
 * Export helpers - PDF via Dompdf, Excel via PhpSpreadsheet.
 */

require_once ROOT_PATH . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function export_pdf(string $html, string $filename): never
{
    $options = new Options();
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . sanitize_filename($filename) . '.pdf"');
    echo $dompdf->output();
    exit;
}

/**
 * @param string[] $headers
 * @param array<int, array<int, mixed>> $rows
 */
function export_excel(array $headers, array $rows, string $filename, string $sheetTitle = 'Report'): never
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($sheetTitle, 0, 31));

    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . sanitize_filename($filename) . '.xlsx"');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function export_csv(array $headers, array $rows, string $filename): never
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . sanitize_filename($filename) . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ',', '"', '\\');
    foreach ($rows as $row) {
        fputcsv($out, $row, ',', '"', '\\');
    }
    fclose($out);
    exit;
}
