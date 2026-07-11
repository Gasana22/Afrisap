<?php

namespace App\Models;

use App\Core\Database;

class ComplianceReport
{
    private const TITLES = [
        'organic' => 'Organic Certification Report',
        'gap' => 'Good Agricultural Practice (GAP) Compliance Report',
        'export' => 'Export Documentation',
        'food_safety' => 'Food Safety Audit Report',
        'carbon' => 'Carbon Reporting Summary',
    ];

    public static function title(string $type): string
    {
        return self::TITLES[$type] ?? 'Compliance Report';
    }

    public static function buildHtml(array $batch, string $type): string
    {
        $productName = $batch['batch_type'] === 'crop' ? ($batch['crop_cycle']['crop_type_name'] ?? '') : ($batch['animal']['species'] ?? '');
        $farmName = $batch['batch_type'] === 'crop' ? ($batch['crop_cycle']['farm_name'] ?? '') : ($batch['animal']['farm_name'] ?? '');

        $body = match ($type) {
            'organic' => self::organicSection($batch),
            'gap' => self::gapSection($batch),
            'export' => self::exportSection($batch),
            'food_safety' => self::foodSafetySection($batch),
            'carbon' => self::carbonSection($batch),
            default => '<p>Unknown report type.</p>',
        };

        $css = 'body{font-family:sans-serif;font-size:12px;color:#222;} h1{font-size:18px;margin-bottom:2px;}
            h2{font-size:14px;margin-top:20px;border-bottom:1px solid #ccc;padding-bottom:3px;}
            .meta{color:#666;font-size:11px;margin-bottom:16px;}
            table{width:100%;border-collapse:collapse;margin-top:8px;}
            th,td{border:1px solid #ddd;padding:5px 7px;text-align:left;font-size:11px;}
            th{background:#f2f2f2;} .badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;color:#fff;}
            .badge-green{background:#0ca30c;} .badge-red{background:#d03b3b;} .disclaimer{font-size:10px;color:#888;margin-top:20px;font-style:italic;}';

        return "<html><head><meta charset='utf-8'><style>{$css}</style></head><body>
            <h1>" . htmlspecialchars(self::title($type)) . "</h1>
            <div class='meta'>
                Batch: <strong>" . htmlspecialchars($batch['batch_code']) . "</strong> &middot;
                Product: " . htmlspecialchars($productName) . " &middot;
                Farm: " . htmlspecialchars($farmName) . "<br>
                Generated: " . htmlspecialchars(date('Y-m-d H:i')) . "
            </div>
            {$body}
        </body></html>";
    }

    private static function organicSection(array $batch): string
    {
        if ($batch['batch_type'] !== 'crop') {
            return '<p>Organic certification reporting currently applies to crop batches.</p>';
        }

        $inputs = CropInput::forCycle((int) $batch['crop_cycle_id']);
        $hasChemical = false;
        $rows = '';
        foreach ($inputs as $i) {
            if ($i['input_type'] === 'chemical') {
                $hasChemical = true;
            }
            $rows .= '<tr><td>' . htmlspecialchars(ucfirst($i['input_type'])) . '</td><td>' . htmlspecialchars($i['supplier_name'] ?? '') . '</td>'
                . '<td>' . htmlspecialchars($i['quantity'] . ' ' . $i['unit']) . '</td><td>' . htmlspecialchars($i['purchase_date'] ?? '') . '</td></tr>';
        }

        $certs = TraceApproval::approvedTypes((int) $batch['id']);
        $certHtml = empty($certs) ? '<p>No approved certifications on record.</p>' : '<ul>' . implode('', array_map(fn($c) => '<li>' . htmlspecialchars($c) . '</li>', $certs)) . '</ul>';

        $statusBadge = $hasChemical
            ? "<span class='badge badge-red'>Synthetic chemical inputs recorded</span>"
            : "<span class='badge badge-green'>No synthetic chemical inputs recorded</span>";

        return "<h2>Input Summary</h2>{$statusBadge}
            <table><thead><tr><th>Type</th><th>Supplier</th><th>Quantity</th><th>Purchase Date</th></tr></thead><tbody>{$rows}</tbody></table>
            <h2>Certifications</h2>{$certHtml}
            <p class='disclaimer'>This report summarizes recorded inputs and certifications. It does not itself constitute organic
            certification; presentation to a certifying body remains the farm's responsibility.</p>";
    }

    private static function gapSection(array $batch): string
    {
        if ($batch['batch_type'] !== 'crop') {
            return '<p>GAP compliance reporting currently applies to crop batches.</p>';
        }

        $activities = FieldActivity::forCycle((int) $batch['crop_cycle_id']);
        $rows = '';
        foreach ($activities as $a) {
            $rows .= '<tr><td>' . htmlspecialchars($a['activity_date']) . '</td><td>' . htmlspecialchars(ucfirst($a['activity_type'])) . '</td>'
                . '<td>' . htmlspecialchars($a['worker_name'] ?? '') . '</td><td>' . htmlspecialchars(ucfirst($a['status'])) . '</td></tr>';
        }

        $total = count($activities);
        $withGps = count(array_filter($activities, fn($a) => $a['gps_lat'] !== null));

        return "<h2>Field Practice Log</h2>
            <table><thead><tr><th>Date</th><th>Activity</th><th>Worker</th><th>Status</th></tr></thead><tbody>{$rows}</tbody></table>
            <h2>Traceability Coverage</h2>
            <p>{$total} field operations recorded, {$withGps} with GPS-verified location.</p>";
    }

    private static function exportSection(array $batch): string
    {
        $qr = TraceQrCode::forBatch((int) $batch['id']);
        $config = require __DIR__ . '/../config/config.php';
        $qrLink = $qr ? rtrim($config['app']['url'], '/') . '/trace/' . $qr['public_token'] : null;

        if ($batch['batch_type'] === 'crop') {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT h.harvest_date, h.quantity, h.unit, h.quality_grade, cs.buyer_name, cs.sale_date, cs.revenue
                FROM harvests h LEFT JOIN crop_sales cs ON cs.harvest_id = h.id WHERE h.crop_cycle_id = :id');
            $stmt->execute(['id' => $batch['crop_cycle_id']]);
            $rows = $stmt->fetchAll();
            $rowsHtml = '';
            foreach ($rows as $r) {
                $rowsHtml .= '<tr><td>' . htmlspecialchars($r['harvest_date']) . '</td><td>' . htmlspecialchars($r['quantity'] . ' ' . $r['unit']) . '</td>'
                    . '<td>' . htmlspecialchars($r['quality_grade'] ?? '') . '</td><td>' . htmlspecialchars($r['buyer_name'] ?? '—') . '</td>'
                    . '<td>' . htmlspecialchars($r['sale_date'] ?? '—') . '</td></tr>';
            }
            $table = "<table><thead><tr><th>Harvest Date</th><th>Quantity</th><th>Grade</th><th>Buyer</th><th>Sale Date</th></tr></thead><tbody>{$rowsHtml}</tbody></table>";
        } else {
            $sale = AnimalSale::forAnimal((int) $batch['animal_id']);
            $table = $sale
                ? '<table><tr><th>Buyer</th><td>' . htmlspecialchars($sale['buyer_name']) . '</td></tr><tr><th>Sale Date</th><td>' . htmlspecialchars($sale['sale_date']) . '</td></tr><tr><th>Price</th><td>' . htmlspecialchars($sale['sale_price']) . '</td></tr></table>'
                : '<p>Not yet sold.</p>';
        }

        $qrHtml = $qrLink ? "<p>Traceability link: {$qrLink}</p>" : '<p>No QR code generated yet for this batch.</p>';

        return "<h2>Shipment / Sale Details</h2>{$table}<h2>Traceability Reference</h2>{$qrHtml}";
    }

    private static function foodSafetySection(array $batch): string
    {
        $rowsHtml = '';
        if ($batch['batch_type'] === 'crop') {
            $records = MonitoringRecord::forCycle((int) $batch['crop_cycle_id']);
            foreach ($records as $r) {
                $rowsHtml .= '<tr><td>' . htmlspecialchars($r['record_date']) . '</td><td>' . htmlspecialchars(ucfirst($r['type'])) . '</td>'
                    . '<td>' . htmlspecialchars($r['severity'] ?? '') . '</td><td>' . htmlspecialchars($r['description'] ?? '') . '</td></tr>';
            }
            $header = '<tr><th>Date</th><th>Type</th><th>Severity</th><th>Description</th></tr>';
        } else {
            $records = AnimalTreatment::forAnimal((int) $batch['animal_id']);
            foreach ($records as $r) {
                $rowsHtml .= '<tr><td>' . htmlspecialchars($r['treatment_date']) . '</td><td>' . htmlspecialchars($r['condition_name']) . '</td>'
                    . '<td>' . htmlspecialchars($r['treatment']) . '</td><td>' . htmlspecialchars($r['administered_by'] ?? '') . '</td></tr>';
            }
            $header = '<tr><th>Date</th><th>Condition</th><th>Treatment</th><th>By</th></tr>';
        }

        $journey = ProductJourney::forBatch((int) $batch['id']);
        $journeyHtml = '';
        foreach ($journey as $j) {
            $journeyHtml .= '<tr><td>' . htmlspecialchars($j['stage_date']) . '</td><td>' . htmlspecialchars(ucfirst($j['stage'])) . '</td><td>' . htmlspecialchars($j['location'] ?? '') . '</td></tr>';
        }

        return "<h2>Health &amp; Safety Records</h2>
            <table><thead>{$header}</thead><tbody>{$rowsHtml}</tbody></table>
            <h2>Storage &amp; Processing Chain of Custody</h2>
            <table><thead><tr><th>Date</th><th>Stage</th><th>Location</th></tr></thead><tbody>{$journeyHtml}</tbody></table>";
    }

    private static function carbonSection(array $batch): string
    {
        $syntheticInputs = 0;
        if ($batch['batch_type'] === 'crop') {
            $inputs = CropInput::forCycle((int) $batch['crop_cycle_id']);
            $syntheticInputs = count(array_filter($inputs, fn($i) => in_array($i['input_type'], ['fertilizer', 'chemical'], true)));
        }

        $journey = ProductJourney::forBatch((int) $batch['id']);
        $distributionStages = count(array_filter($journey, fn($j) => in_array($j['stage'], ['distribution', 'delivered'], true)));

        return "<h2>Activity Summary</h2>
            <table>
                <tr><th>Synthetic fertilizer/chemical applications</th><td>{$syntheticInputs}</td></tr>
                <tr><th>Distribution/delivery movements recorded</th><td>{$distributionStages}</td></tr>
            </table>
            <p class='disclaimer'>This is a foundational activity summary derived from operational records, not a certified
            carbon footprint calculation. A full carbon accounting requires emission factors per input/transport mode which
            are not yet modeled in this system.</p>";
    }
}
