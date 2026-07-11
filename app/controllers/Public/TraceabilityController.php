<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\FileUpload;
use App\Core\Validator;
use App\Models\ProductJourney;
use App\Models\TraceApproval;
use App\Models\TraceBatch;
use App\Models\TraceDocument;
use App\Models\TraceQrCode;

class TraceabilityController extends Controller
{
    public function index(): void
    {
        $this->view('public/traceability/index', ['pageTitle' => 'Traceability', 'batches' => TraceBatch::forOrganization(Auth::organizationId())]);
    }

    /** The tenant-isolation gate for every action below. */
    private function requireOwnedBatch(int $id): array
    {
        $batch = TraceBatch::findInOrganization($id, Auth::organizationId());
        if (!$batch) {
            $this->flash('danger', 'Batch not found.');
            $this->redirect('/traceability');
        }
        return $batch;
    }

    public function forCropCycle(array $params): void
    {
        $traceBatchId = TraceBatch::findIdByCropCycle((int) $params['id']);
        $this->redirect($traceBatchId ? "/traceability/{$traceBatchId}" : '/traceability');
    }

    public function forAnimal(array $params): void
    {
        $traceBatchId = TraceBatch::findIdByAnimal((int) $params['id']);
        $this->redirect($traceBatchId ? "/traceability/{$traceBatchId}" : '/traceability');
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $batch = $this->requireOwnedBatch($id);

        $this->view('public/traceability/show', [
            'pageTitle' => 'Batch ' . $batch['batch_code'],
            'batch' => $batch,
            'timeline' => TraceBatch::timeline($batch),
            'profit' => TraceBatch::profit($batch),
            'gpsPoints' => TraceBatch::gpsPoints($batch),
            'documents' => TraceDocument::forBatch($id),
            'approvals' => TraceApproval::forBatch($id),
            'journeyStages' => ProductJourney::forBatch($id),
            'qrCode' => TraceQrCode::forBatch($id),
        ]);
    }

    public function updateStatus(array $params): void
    {
        $id = (int) $params['id'];
        $batch = $this->requireOwnedBatch($id);

        $status = $this->input('status');
        $validator = (new Validator(['status' => $status]))->in('status', ['active', 'completed', 'recalled'], 'Status');
        if ($validator->fails()) {
            $this->redirect("/traceability/{$id}");
        }

        TraceBatch::updateStatus($id, $status);
        AuditLogger::log('update_status', 'trace_batches', (string) $id, $batch, ['status' => $status]);

        $this->flash('success', 'Batch status updated.');
        $this->redirect("/traceability/{$id}");
    }

    public function generateQr(array $params): void
    {
        $id = (int) $params['id'];
        $this->requireOwnedBatch($id);

        $config = require __DIR__ . '/../../config/config.php';
        $qr = TraceQrCode::generate($id, $config['app']['url']);
        AuditLogger::log('generate_qr', 'trace_qr_codes', (string) $qr['id'], null, ['trace_batch_id' => $id]);

        $this->flash('success', 'QR code generated.');
        $this->redirect("/traceability/{$id}");
    }

    public function addDocument(array $params): void
    {
        $id = (int) $params['id'];
        $this->requireOwnedBatch($id);

        $validator = (new Validator($_POST))->required('document_type', 'Document type');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/traceability/{$id}");
        }

        try {
            $filePath = FileUpload::storeImage('document', 'trace-documents');
        } catch (\RuntimeException $e) {
            $this->flash('danger', 'Document upload failed: ' . $e->getMessage());
            $this->redirect("/traceability/{$id}");
        }

        if (!$filePath) {
            $this->flash('danger', 'Please choose a file to upload.');
            $this->redirect("/traceability/{$id}");
        }

        $docId = TraceDocument::create($id, $this->input('document_type'), $filePath, Auth::id(), $this->input('notes'));
        AuditLogger::log('create', 'trace_documents', (string) $docId, null, ['trace_batch_id' => $id]);

        $this->flash('success', 'Document uploaded.');
        $this->redirect("/traceability/{$id}");
    }

    public function addApproval(array $params): void
    {
        $id = (int) $params['id'];
        $this->requireOwnedBatch($id);

        $validator = (new Validator($_POST))->required('approval_type', 'Approval type');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/traceability/{$id}");
        }

        $approvalId = TraceApproval::create($id, $this->input('approval_type'), $this->input('notes'));
        AuditLogger::log('create', 'trace_approvals', (string) $approvalId, null, ['trace_batch_id' => $id]);

        $this->flash('success', 'Approval request added.');
        $this->redirect("/traceability/{$id}");
    }

    public function updateApprovalStatus(array $params): void
    {
        $approvalId = (int) $params['id'];
        $approval = TraceApproval::find($approvalId);
        if (!$approval) {
            $this->redirect('/traceability');
        }
        $this->requireOwnedBatch((int) $approval['trace_batch_id']);

        $status = $this->input('status');
        $validator = (new Validator(['status' => $status]))->in('status', ['approved', 'rejected'], 'Status');
        if ($validator->fails()) {
            $this->redirect('/traceability/' . $approval['trace_batch_id']);
        }

        TraceApproval::updateStatus($approvalId, $status, Auth::id());
        AuditLogger::log('update_status', 'trace_approvals', (string) $approvalId, $approval, ['status' => $status]);

        $this->flash('success', 'Approval updated.');
        $this->redirect('/traceability/' . $approval['trace_batch_id']);
    }

    public function addJourneyStage(array $params): void
    {
        $id = (int) $params['id'];
        $this->requireOwnedBatch($id);

        $validator = (new Validator($_POST))->required('stage', 'Stage')->required('stage_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/traceability/{$id}");
        }

        $stageId = ProductJourney::create($id, [
            'stage' => $this->input('stage'),
            'stage_date' => $this->input('stage_date'),
            'location' => $this->input('location'),
            'notes' => $this->input('notes'),
        ], Auth::id());
        AuditLogger::log('create', 'product_journey', (string) $stageId, null, ['trace_batch_id' => $id]);

        $this->flash('success', 'Journey stage recorded.');
        $this->redirect("/traceability/{$id}");
    }
}
