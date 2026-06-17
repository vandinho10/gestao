<?php
// Carrega variáveis de ambiente do arquivo .env se existir
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

require_once __DIR__ . '/app/Core/Config.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/Security.php';
require_once __DIR__ . '/app/Core/Auth.php';
require_once __DIR__ . '/app/Core/Router.php';

require_once __DIR__ . '/app/Models/Despesa.php';
require_once __DIR__ . '/app/Models/Prestacao.php';

require_once __DIR__ . '/app/Controllers/BaseController.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/MainController.php';
require_once __DIR__ . '/app/Controllers/AcaoController.php';

$router = new \App\Core\Router();

// Legacy mapping from acoes.php
if (isset($_GET['acao'])) {
    $acao = $_GET['acao'];
    $map = [
        'lancar' => '/acoes/lancar',
        'agrupar' => '/acoes/agrupar',
        'editar' => '/acoes/editar',
        'mudar_status' => '/acoes/mudarStatus',
        'remover_item' => '/acoes/removerItem',
        'excluir_item' => '/acoes/excluirItem',
        'restaurar_item' => '/acoes/restaurarItem',
        'excluir_permanente' => '/acoes/excluirPermanente',
        'get_deletadas' => '/acoes/getDeletadas',
        'get_itens_pendentes' => '/acoes/getPendentes',
        'get_itens_pendentes_admin' => '/acoes/getPendentes',
        'get_grupos_prestacao' => '/acoes/getGrupos',
        'get_itens_data_tipo_prestacao' => '/acoes/getItensPrestacao'
    ];
    if (isset($map[$acao])) {
        $_GET['route'] = $map[$acao];
    }
}

$router->get('/', 'MainController@index');
$router->get('/admin', 'MainController@admin');

$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

$router->post('/acoes/lancar', 'AcaoController@lancar');
$router->post('/acoes/agrupar', 'AcaoController@agrupar');
$router->post('/acoes/editar', 'AcaoController@editar');
$router->post('/acoes/mudarStatus', 'AcaoController@mudarStatus');
$router->post('/acoes/removerItem', 'AcaoController@removerItem');
$router->post('/acoes/excluirItem', 'AcaoController@excluirItem');
$router->post('/acoes/restaurarItem', 'AcaoController@restaurarItem');
$router->post('/acoes/excluirPermanente', 'AcaoController@excluirPermanente');

$router->get('/acoes/getPendentes', 'AcaoController@getPendentes');
$router->get('/acoes/getDeletadas', 'AcaoController@getDeletadas');
$router->get('/acoes/getGrupos', 'AcaoController@getGrupos');
$router->get('/acoes/getItensPrestacao', 'AcaoController@getItensPrestacao');

$router->dispatch();