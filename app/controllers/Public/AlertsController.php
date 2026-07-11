<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AlertEngine;

class AlertsController extends Controller
{
    public function index(): void
    {
        $this->view('public/alerts/index', ['pageTitle' => 'Alerts', 'alerts' => AlertEngine::all(Auth::organizationId())]);
    }
}
