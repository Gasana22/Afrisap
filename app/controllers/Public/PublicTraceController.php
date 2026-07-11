<?php

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ProductJourney;
use App\Models\TraceApproval;
use App\Models\TraceBatch;
use App\Models\TraceDocument;

class PublicTraceController extends Controller
{
    public function show(array $params): void
    {
        $batch = TraceBatch::findByToken($params['token']);

        if (!$batch) {
            http_response_code(404);
            $this->renderPublic('public/not-found', ['pageTitle' => 'Not Found']);
            return;
        }

        $origin = $this->originInfo($batch);

        $this->renderPublic('public/trace', [
            'pageTitle' => $batch['batch_code'],
            'batch' => $batch,
            'origin' => $origin,
            'timeline' => TraceBatch::timeline($batch),
            'journeyStages' => ProductJourney::forBatch((int) $batch['id']),
            'certificates' => TraceDocument::approvedCertificates((int) $batch['id']),
            'approvedCertifications' => TraceApproval::approvedTypes((int) $batch['id']),
        ]);
    }

    private function originInfo(array $batch): array
    {
        $pdo = Database::connection();

        if ($batch['batch_type'] === 'crop') {
            $stmt = $pdo->prepare("SELECT ct.name AS product_name, f.name AS farm_name, f.district, f.village,
                    p.gps_lat, p.gps_lng, cc.start_date AS production_date,
                    (SELECT MIN(harvest_date) FROM harvests WHERE crop_cycle_id = cc.id) AS harvest_date,
                    (SELECT GROUP_CONCAT(DISTINCT worker_name SEPARATOR ', ') FROM field_activities WHERE crop_cycle_id = cc.id AND worker_name IS NOT NULL) AS workers
                FROM crop_cycles cc
                JOIN crop_types ct ON ct.id = cc.crop_type_id
                JOIN plots p ON p.id = cc.plot_id
                JOIN blocks b ON b.id = p.block_id
                JOIN farms f ON f.id = b.farm_id
                WHERE cc.id = :id");
            $stmt->execute(['id' => $batch['crop_cycle_id']]);
        } else {
            $stmt = $pdo->prepare("SELECT CONCAT(a.species, COALESCE(CONCAT(' - ', a.name), '')) AS product_name,
                    f.name AS farm_name, f.district, f.village, f.gps_lat, f.gps_lng, a.birth_date AS production_date,
                    NULL AS harvest_date,
                    (SELECT GROUP_CONCAT(DISTINCT administered_by SEPARATOR ', ') FROM animal_treatments WHERE animal_id = a.id AND administered_by IS NOT NULL) AS workers
                FROM animals a
                JOIN farms f ON f.id = a.farm_id
                WHERE a.id = :id");
            $stmt->execute(['id' => $batch['animal_id']]);
        }

        return $stmt->fetch() ?: [];
    }

    private function renderPublic(string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        require __DIR__ . "/../../views/{$view}.php";
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/public.php';
    }
}
