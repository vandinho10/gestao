<?php
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Config;

class BaseController {
    protected function view($view, $data = []) {
        extract($data);
        $isAdmin = Auth::isAdmin();
        $usuario = Auth::user();
        require_once __DIR__ . '/../Views/layout.php';
    }

    protected function partial($view, $data = []) {
        extract($data);
        require __DIR__ . '/../Views/' . $view . '.php';
    }

    protected function redirect($url) {
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            header("Location: " . $url);
        } else {
            header("Location: " . Config::BASE_URL . ltrim($url, '/'));
        }
        exit;
    }

    protected function checkAuth() {
        if (!Auth::check()) {
            $this->redirect('login');
        }
    }

    protected function checkAdmin() {
        $this->checkAuth();
        if (!Auth::isAdmin()) {
            $this->redirect('');
        }
    }
}
