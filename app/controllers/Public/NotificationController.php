<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $this->view('public/notifications/index', [
            'pageTitle' => 'Notifications',
            'notifications' => Notification::forUser(Auth::id(), 100),
        ]);
    }

    public function markRead(array $params): void
    {
        $this->requireLogin();
        Notification::markRead((int) $params['id'], Auth::id());
        $this->redirect($this->input('redirect', '/notifications'));
    }

    public function markAllRead(): void
    {
        $this->requireLogin();
        Notification::markAllRead(Auth::id());
        $this->redirect('/notifications');
    }
}
