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

            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data),
                    'ignore_errors' => true,
                    'timeout' => 10
                ]
            ];

            $context  = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);

            if ($result) {
                $status_line = $http_response_header[0];
                preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
                $status = $match[1] ?? '500';

                $resp = json_decode($result, true);

                if ($status == '200' && isset($resp['token'])) {
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
