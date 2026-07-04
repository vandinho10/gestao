<?php
namespace App\Core;

class Auth {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => getenv('GESTAO_COOKIE_DOMAIN') ?: '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    private static function checkAuthToken($token) {
        $url = getenv('GESTAO_AUTH_API_URL') . '/auth/profile';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $token],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 200 && $result) {
            return json_decode($result, true);
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
