<?php
namespace App\Core;

class Config {
    const DB_HOST = 'db';
    const DB_NAME = 'gestao_contas';
    const DB_USER = 'gestao_contas';
    const TETO_REFEICAO = 126.96;
    const TIPOS_DESPESA = ["Refeição", "Carro", "Adm", "Outros"];
    const BASE_URL = '/gestao/';

    public static function getDbPass() {
        return getenv('GESTAO_DB_PASS');
    }
}
