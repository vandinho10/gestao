<?php
namespace App\Controllers;
use App\Models\Despesa;
use App\Models\Prestacao;
use App\Core\Auth;
use App\Core\Security;

class MainController extends BaseController {
    public function index() {
        $this->checkAuth();
        $despesaModel = new Despesa();
        $prestacaoModel = new Prestacao();
        $userId = Auth::userId();

        $pendentes = $despesaModel->getPendentesAgrupadas($userId);

        $exibir_todos = isset($_GET['exibir_todos']) && $_GET['exibir_todos'] == '1';
        $prestacoes = $prestacaoModel->getTodas($exibir_todos, $userId);

        $this->view('home', [
            'pendentes' => $pendentes,
            'prestacoes' => $prestacoes,
            'exibir_todos' => $exibir_todos,
            'titulo' => 'Meu Painel'
        ]);
    }

    public function admin() {
        $this->checkAdmin();
        $despesaModel = new Despesa();
        $prestacaoModel = new Prestacao();

        $pendentes = $despesaModel->getPendentesAgrupadas(null); // all users

        $exibir_todos = isset($_GET['exibir_todos']) && $_GET['exibir_todos'] == '1';
        $prestacoes = $prestacaoModel->getTodas($exibir_todos, null); // all users

        $this->view('admin', [
            'pendentes' => $pendentes,
            'prestacoes' => $prestacoes,
            'exibir_todos' => $exibir_todos,
            'titulo' => 'Painel Administrador'
        ]);
    }
}
