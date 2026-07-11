<?php

namespace App\Core;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExporter
{
    /**
     * Streams a simple tabular report as an XLSX download and ends the request.
     *
     * @param string[] $headers
     * @param array<int, array<int, string>> $rows
     */
    public static function streamTable(string $title, array $headers, array $rows, string $filename): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));

        foreach ($headers as $col => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue("{$columnLetter}1", $header);
            $sheet->getStyle("{$columnLetter}1")->getFont()->setBold(true);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $col => $cell) {
                $columnLetter = Coordinate::stringFromColumnIndex($col + 1);
                $sheet->setCellValue("{$columnLetter}" . ($rowIndex + 2), $cell);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
