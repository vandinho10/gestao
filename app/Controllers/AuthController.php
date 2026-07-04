<?php
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Config;

class AuthController extends BaseController {
    public function login() {
        if (Auth::check()) {
            $this->redirect('');
        }

        $erro = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
            $url = getenv('GESTAO_AUTH_API_URL') . '/auth/login';
            $data = [
                'username' => trim($_POST['username']),
                'password' => $_POST['password']
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result) {
                $resp = json_decode($result, true);

                if ($httpcode === 200 && isset($resp['token'])) {
                    $_SESSION['logado'] = true;
                    $_SESSION['token'] = $resp['token'];

                    $host_server = $_SERVER['HTTP_HOST'] ?? '';
                    $cookie_domain = getenv('GESTAO_COOKIE_DOMAIN');
                    $domain = $cookie_domain ?: '';

                    $expiracao = time() + (7 * 24 * 60 * 60);
                    if ($domain !== '') {
                        setcookie("token", $resp['token'], $expiracao, "/", $domain, true, true);
                    } else {
                        setcookie("token", $resp['token'], $expiracao, "/", "", false, true);
                    }

                    $this->redirect('');
                } else {
                    $erro = $resp['error'] ?? "Usuário ou senha incorretos!";
                }
            } else {
                $erro = "Erro ao conectar com a API de autenticação.";
            }
        }

        require_once __DIR__ . '/../Views/login.php';
    }

    public function logout() {
        Auth::logout();
    }
}
