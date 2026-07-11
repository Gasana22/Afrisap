<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\ExcelExporter;
use App\Core\PdfExporter;
use App\Models\AnalyticsReport;

class ReportsController extends Controller
{
    public function index(): void
    {
        $this->view('reports/index', ['pageTitle' => 'Reports']);
    }

    public function cropYield(): void
    {
        $rows = Database::connection()->query("SELECT cc.batch_code, ct.name AS crop_type, f.name AS farm_name,
                p.plot_code, p.size_hectares, COALESCE(SUM(h.quantity), 0) AS total_yield, cc.status
            FROM crop_cycles cc
            JOIN crop_types ct ON ct.id = cc.crop_type_id
            JOIN plots p ON p.id = cc.plot_id
            JOIN blocks b ON b.id = p.block_id
            JOIN farms f ON f.id = b.farm_id
            LEFT JOIN harvests h ON h.crop_cycle_id = cc.id
            GROUP BY cc.id, cc.batch_code, ct.name, f.name, p.plot_code, p.size_hectares, cc.status
            ORDER BY cc.batch_code")->fetchAll();

        $headers = ['Batch Code', 'Crop Type', 'Farm', 'Plot', 'Hectares', 'Total Yield', 'Yield/Hectare', 'Status'];
        $tableRows = array_map(function ($r) {
            $perHectare = $r['size_hectares'] > 0 ? round($r['total_yield'] / $r['size_hectares'], 2) : 0;
            return [$r['batch_code'], $r['crop_type'], $r['farm_name'], $r['plot_code'], $r['size_hectares'], $r['total_yield'], $perHectare, $r['status']];
        }, $rows);

        $this->exportOrRender('reports/crop-yield', 'Crop Yield Report', $headers, $tableRows, ['rows' => $rows]);
    }

    public function livestockProduction(): void
    {
        $rows = Database::connection()->query("SELECT a.animal_code, a.species, a.name, f.name AS farm_name, a.status,
                COALESCE(SUM(ap.quantity), 0) AS total_production
            FROM animals a
            JOIN farms f ON f.id = a.farm_id
            LEFT JOIN animal_production ap ON ap.animal_id = a.id
            GROUP BY a.id, a.animal_code, a.species, a.name, f.name, a.status
            ORDER BY a.animal_code")->fetchAll();

        $headers = ['Animal ID', 'Species', 'Name', 'Farm', 'Total Production', 'Status'];
        $tableRows = array_map(fn($r) => [$r['animal_code'], $r['species'], $r['name'] ?? '', $r['farm_name'], $r['total_production'], $r['status']], $rows);

        $this->exportOrRender('reports/livestock-production', 'Livestock Production Report', $headers, $tableRows, ['rows' => $rows]);
    }

    public function workerProductivity(): void
    {
        $rows = AnalyticsReport::workerProductivity(null);

        $headers = ['Worker', 'Farm', 'Tasks Verified', 'Days Present'];
        $tableRows = array_map(fn($r) => [$r['name'], $r['farm_name'], $r['tasks_verified'], $r['days_present']], $rows);

        $this->exportOrRender('reports/worker-productivity', 'Worker Productivity Report', $headers, $tableRows, ['rows' => $rows]);
    }

    public function dailyActivities(): void
    {
        $from = $this->input('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to = $this->input('to') ?: date('Y-m-d');

        $stmt = Database::connection()->prepare("SELECT fa.activity_date, fa.activity_type, fa.worker_name, fa.status, fa.cost,
                cc.batch_code, f.name AS farm_name
            FROM field_activities fa
            JOIN crop_cycles cc ON cc.id = fa.crop_cycle_id
            JOIN plots p ON p.id = cc.plot_id
            JOIN blocks b ON b.id = p.block_id
            JOIN farms f ON f.id = b.farm_id
            WHERE fa.activity_date BETWEEN :from AND :to
            ORDER BY fa.activity_date DESC");
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();

        $headers = ['Date', 'Batch Code', 'Farm', 'Activity', 'Worker', 'Status', 'Cost'];
        $tableRows = array_map(fn($r) => [$r['activity_date'], $r['batch_code'], $r['farm_name'], ucfirst($r['activity_type']), $r['worker_name'] ?? '', $r['status'], $r['cost'] ?? ''], $rows);

        $this->exportOrRender('reports/daily-activities', 'Daily Activities Report', $headers, $tableRows, ['rows' => $rows, 'from' => $from, 'to' => $to]);
    }

    private function exportOrRender(string $view, string $title, array $headers, array $tableRows, array $viewData): void
    {
        $format = $this->input('format');
        $filenameBase = strtolower(str_replace(' ', '-', $title)) . '-' . date('Y-m-d');

        if ($format === 'pdf') {
            PdfExporter::streamTable($title, $headers, $tableRows, "{$filenameBase}.pdf");
            return;
        }

        if ($format === 'excel') {
            ExcelExporter::streamTable($title, $headers, $tableRows, "{$filenameBase}.xlsx");
            return;
        }

        $this->view($view, array_merge(['pageTitle' => $title, 'title' => $title], $viewData));
    }
}
