<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AlertEngine;

class AlertsController extends Controller
{
    public function index(): void
    {
        $this->view('alerts/index', ['pageTitle' => 'Alerts', 'alerts' => AlertEngine::all(Auth::organizationId())]);
    }
}
