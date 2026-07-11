<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class MapController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $farmsStmt = $pdo->prepare("SELECT id, name, gps_lat, gps_lng, district, village FROM farms
            WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL AND organization_id = :org_id");
        $farmsStmt->execute(['org_id' => $orgId]);
        $farms = $farmsStmt->fetchAll();

        $plotsStmt = $pdo->prepare("SELECT p.id, p.plot_code, p.gps_lat, p.gps_lng, b.name AS block_name, f.name AS farm_name
            FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
            WHERE p.gps_lat IS NOT NULL AND p.gps_lng IS NOT NULL AND f.organization_id = :org_id");
        $plotsStmt->execute(['org_id' => $orgId]);
        $plots = $plotsStmt->fetchAll();

        $activitiesStmt = $pdo->prepare("SELECT fa.gps_lat, fa.gps_lng, fa.activity_type, fa.activity_date, fa.worker_name, cc.batch_code
            FROM field_activities fa
            JOIN crop_cycles cc ON cc.id = fa.crop_cycle_id
            JOIN plots p ON p.id = cc.plot_id
            JOIN blocks b ON b.id = p.block_id
            JOIN farms f ON f.id = b.farm_id
            WHERE fa.gps_lat IS NOT NULL AND fa.gps_lng IS NOT NULL AND f.organization_id = :org_id
            ORDER BY fa.activity_date DESC LIMIT 300");
        $activitiesStmt->execute(['org_id' => $orgId]);
        $activities = $activitiesStmt->fetchAll();

        $this->view('public/maps/index', [
            'pageTitle' => 'Farm Map',
            'farms' => $farms,
            'plots' => $plots,
            'activities' => $activities,
        ]);
    }
}
