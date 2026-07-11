<?php

namespace App\Core;

abstract class Controller
{
    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login');
            exit;
        }
    }

    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data);
        $currentUser = Auth::user();
        $viewFile = __DIR__ . "/../views/{$view}.php";

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require __DIR__ . "/../views/layouts/{$layout}.php";
    }

    protected function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
