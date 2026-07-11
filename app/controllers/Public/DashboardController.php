<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Models\AnalyticsReport;
use App\Models\Farm;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $farmIds = $orgId !== null ? Farm::idsForOrganization($orgId) : [];

        if (empty($farmIds)) {
            $stats = ['farms' => 0, 'blocks' => 0, 'plots' => 0, 'users' => count(User::forOrganization($orgId ?? 0))];
        } else {
            $placeholders = implode(',', array_fill(0, count($farmIds), '?'));
            $blockCount = $pdo->prepare("SELECT COUNT(*) FROM blocks WHERE farm_id IN ({$placeholders})");
            $blockCount->execute($farmIds);
            $plotCount = $pdo->prepare("SELECT COUNT(*) FROM plots p JOIN blocks b ON b.id = p.block_id WHERE b.farm_id IN ({$placeholders})");
            $plotCount->execute($farmIds);

            $stats = [
                'farms' => count($farmIds),
                'blocks' => (int) $blockCount->fetchColumn(),
                'plots' => (int) $plotCount->fetchColumn(),
                'users' => count(User::forOrganization($orgId)),
            ];
        }

        $analytics = null;
        if (Auth::hasPermission('reports.view')) {
            $analytics = [
                'revenueTrend' => AnalyticsReport::revenueExpenseTrend(6, $farmIds),
                'revenueGrowth' => AnalyticsReport::revenueGrowth($farmIds),
                'costYieldPerHectare' => AnalyticsReport::costYieldPerHectare($farmIds),
                'workerProductivity' => AnalyticsReport::workerProductivity(8, $farmIds),
                'livestockMortality' => AnalyticsReport::livestockMortality($farmIds),
            ];
        }

        $this->view('public/dashboard/index', ['pageTitle' => 'Dashboard', 'stats' => $stats, 'analytics' => $analytics]);
    }

    public function profile(): void
    {
        $this->requireLogin();
        $user = User::find(Auth::id());
        $this->view('public/dashboard/profile', ['pageTitle' => 'My Profile', 'profileUser' => $user]);
    }

    public function updateProfile(): void
    {
        $this->requireLogin();
        $userId = Auth::id();
        $before = User::find($userId);

        $validator = (new Validator($_POST))->required('name', 'Name')->required('email', 'Email')->email('email');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/profile');
        }

        $data = [
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'role_id' => $before['role_id'],
            'status' => $before['status'],
            'mfa_enabled' => $before['mfa_enabled'],
        ];

        $newPassword = (string) $this->input('password', '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                $this->flash('danger', 'New password must be at least 8 characters.');
                $this->redirect('/profile');
            }
            $data['password'] = $newPassword;
        }

        User::update($userId, $data);
        AuditLogger::log('update', 'users', (string) $userId, $before, $data);

        $this->flash('success', 'Profile updated.');
        $this->redirect('/profile');
    }
}
