<?php

namespace App\Controllers\Platform;

use App\Core\Auth;
use App\Core\Controller;

/**
 * Base for every controller in the platform-admin portal. The router's
 * permission-string check already excludes tenant roles in practice (no
 * tenant role holds any platform-module permission), but this is the
 * explicit, defense-in-depth version of that guarantee: a tenant session
 * can never reach a Platform\* action, full stop, regardless of what
 * permissions its role happens to carry.
 */
abstract class PlatformController extends Controller
{
    public function __construct()
    {
        if (!Auth::isPlatform()) {
            if (!Auth::check()) {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/platform';
                header('Location: /platform/login');
                exit;
            }
            http_response_code(403);
            require __DIR__ . '/../../views/errors/403.php';
            exit;
        }
    }

    protected function view(string $view, array $data = [], string $layout = 'platform'): void
    {
        parent::view($view, $data, $layout);
    }
}
