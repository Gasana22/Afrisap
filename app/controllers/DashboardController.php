<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Models\AnalyticsReport;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $pdo = Database::connection();
        $stats = [
            'farms' => (int) $pdo->query('SELECT COUNT(*) FROM farms')->fetchColumn(),
            'blocks' => (int) $pdo->query('SELECT COUNT(*) FROM blocks')->fetchColumn(),
            'plots' => (int) $pdo->query('SELECT COUNT(*) FROM plots')->fetchColumn(),
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        ];

        $analytics = null;
        if (Auth::hasPermission('reports.view')) {
            $analytics = [
                'revenueTrend' => AnalyticsReport::revenueExpenseTrend(6),
                'revenueGrowth' => AnalyticsReport::revenueGrowth(),
                'costYieldPerHectare' => AnalyticsReport::costYieldPerHectare(),
                'workerProductivity' => AnalyticsReport::workerProductivity(8),
                'livestockMortality' => AnalyticsReport::livestockMortality(),
            ];
        }

        $this->view('dashboard/index', ['pageTitle' => 'Dashboard', 'stats' => $stats, 'analytics' => $analytics]);
    }

    public function profile(): void
    {
        $this->requireLogin();
        $user = User::find(Auth::id());
        $this->view('dashboard/profile', ['pageTitle' => 'My Profile', 'profileUser' => $user]);
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
