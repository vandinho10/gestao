<?php
namespace App\Core;

class Auth {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private static function checkAuthToken($token) {
        $url = getenv('GESTAO_AUTH_API_URL') . '/auth/profile';
        $options = [
            'http' => [
                'header'  => "Authorization: Bearer " . $token . "\r\n",
                'method'  => 'GET',
                'ignore_errors' => true,
                'timeout' => 5
            ]
        ];
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result) {
            $status_line = $http_response_header[0];
            preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
            if (isset($match[1]) && $match[1] == '200') {
                return json_decode($result, true);
            }
        }
        return false;
    }

    public static function check() {
        self::init();

        if (isset($_SESSION['logado']) && $_SESSION['logado'] === true && isset($_SESSION['user_profile'])) {
            return true;
        } else if (isset($_COOKIE['token'])) {
            $profile = self::checkAuthToken($_COOKIE['token']);
            if ($profile && isset($profile['user'])) {
                $_SESSION['logado'] = true;
                $_SESSION['user_profile'] = $profile['user'];
                return true;
            } else {
                self::logout(false);
            }
        }
        return false;
    }

    public static function user() {
        return $_SESSION['user_profile'] ?? null;
    }

    public static function userId() {
        $u = self::user();
        return $u['id'] ?? 0;
    }

    public static function isAdmin() {
        $u = self::user();
        $nivel = $u['nivel'] ?? ($u['access_level'] ?? 0);
        return ($nivel >= 5);
    }

    public static function logout($redirect = true) {
        self::init();
        $_SESSION = [];
        session_destroy();
        setcookie('token', '', time() - 3600, '/');

        $host_server = $_SERVER['HTTP_HOST'] ?? '';
        $cookie_domain = getenv('GESTAO_COOKIE_DOMAIN');
        if ($cookie_domain) {
            setcookie('token', '', time() - 3600, '/', $cookie_domain);
        } else {
            // Fallback: extrai domínio principal do host
            $parts = explode('.', $host_server);
            if (count($parts) >= 2) {
                $main_domain = '.' . implode('.', array_slice($parts, -2));
                setcookie('token', '', time() - 3600, '/', $main_domain);
            }
        }

        if ($redirect) {
            header("Location: " . Config::BASE_URL . "login");
            exit;
        }
    }
}
