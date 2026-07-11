<?php

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\PdfExporter;
use App\Models\ComplianceReport;
use App\Models\TraceBatch;

class ComplianceController extends Controller
{
    private const ALLOWED_TYPES = ['organic', 'gap', 'export', 'food_safety', 'carbon'];

    public function generate(array $params): void
    {
        $id = (int) $params['id'];
        $type = $params['type'];

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $this->flash('danger', 'Unknown compliance report type.');
            $this->redirect("/traceability/{$id}");
        }

        $batch = TraceBatch::find($id);
        if (!$batch) {
            $this->flash('danger', 'Batch not found.');
            $this->redirect('/traceability');
        }

        $html = ComplianceReport::buildHtml($batch, $type);
        AuditLogger::log('generate_compliance_report', 'trace_batches', (string) $id, null, ['type' => $type]);

        $filename = $type . '-' . $batch['batch_code'] . '.pdf';
        PdfExporter::streamHtml($html, $filename);
    }
}
