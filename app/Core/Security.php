<?php
namespace App\Core;

class Security {
    public static function checkCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token_enviado = $_POST['csrf_token'] ?? '';
            if (empty($token_enviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $token_enviado)) {
                die('Falha na validação de segurança (Token CSRF inválido). Ação bloqueada.');
            }
        }
    }

    public static function generateCsrf() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags((string)$data), ENT_QUOTES, 'UTF-8');
    }
}
