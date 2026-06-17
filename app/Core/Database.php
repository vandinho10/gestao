<?php
namespace App\Core;
use PDO;
use PDOException;

class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=utf8";
                self::$instance = new PDO($dsn, Config::DB_USER, Config::getDbPass());
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Erro de conexão com o banco de dados.");
            }
        }
        return self::$instance;
    }
}
