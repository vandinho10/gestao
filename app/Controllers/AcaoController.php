<?php
namespace App\Controllers;
use App\Models\Despesa;
use App\Models\Prestacao;
use App\Core\Auth;
use App\Core\Security;
use App\Core\Config;

class AcaoController extends BaseController {

    public function __construct() {
        $this->checkAuth();
        // Ações que alteram dados requerem POST e CSRF
        $acoes_get = ['getPendentes', 'getGrupos', 'getItensPrestacao', 'getDeletadas'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $action = basename($uri);
        if (isset($_GET['route'])) {
            $action = basename($_GET['route']);
        }

        if (!in_array($action, $acoes_get)) {
            Security::checkCsrf();
        }
    }

    public function lancar() {
        if (isset($_POST['data'], $_POST['tipo'], $_POST['valor'])) {
            $tipo = $_POST['tipo'];
            $valor = (float)$_POST['valor'];

            if (in_array($tipo, Config::TIPOS_DESPESA) && $valor > 0) {
                $model = new Despesa();
                $model->criar(Auth::userId(), $_POST['data'], $tipo, $valor);
            }
        }
        $this->redirect($this->cleanUrl($_SERVER['HTTP_REFERER'] ?? ''));
    }

    private function cleanUrl($url) {
        if (empty($url)) return '';
        $parsed = parse_url($url);
        if (!$parsed) return $url;

        $query = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
            unset($query['aviso'], $query['numero'], $query['itens']);
        }

        $clean = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') . (isset($parsed['port']) ? ':' . $parsed['port'] : '') . ($parsed['path'] ?? '/');
        if (!empty($query)) {
            $clean .= '?' . http_build_query($query);
        }
        return $clean;
    }

    public function agrupar() {
        $numero = Security::sanitize($_POST['numero_prestacao'] ?? '');
        $confirmado = isset($_GET['confirmado']);
        $todos_ids = [];

        if (isset($_POST['grupos_ids']) && is_array($_POST['grupos_ids'])) {
            foreach ($_POST['grupos_ids'] as $grupo) {
                $todos_ids = array_merge($todos_ids, explode(',', $grupo));
            }
        } elseif (isset($_POST['itens_diretos']) && is_array($_POST['itens_diretos'])) {
            $todos_ids = $_POST['itens_diretos'];
        }

        $cleanRef = $this->cleanUrl($_SERVER['HTTP_REFERER'] ?? '');

        if (empty($todos_ids) || trim($numero) === '') {
            $this->redirect($cleanRef);
        }

        $prestacaoModel = new Prestacao();
        $isAdmin = Auth::isAdmin();
        $userId = Auth::userId();

        $prestacao = $prestacaoModel->getByNumero($numero, $isAdmin ? null : $userId);

        if ($prestacao) {
            if (in_array($prestacao['status'], ['PAGO', 'CANCELADO'])) {
                $this->redirect($cleanRef . (strpos($cleanRef, '?') !== false ? '&' : '?') . "aviso=bloqueado_agrupar");
            }
            if (!$confirmado) {
                $ids_string = implode(',', Security::sanitize($todos_ids));
                $this->redirect($cleanRef . (strpos($cleanRef, '?') !== false ? '&' : '?') . "aviso=duplicado&numero=" . urlencode($numero) . "&itens=" . urlencode($ids_string));
            }
        }

        $prestacaoModel->agrupar($numero, $todos_ids, $userId, $isAdmin);
        $this->redirect($cleanRef);
    }

    public function editar() {
        if (isset($_POST['data'], $_POST['tipo'], $_POST['valor'], $_POST['id'])) {
            $id = (int)$_POST['id'];
            $tipo = $_POST['tipo'];
            $valor = (float)$_POST['valor'];

            if (in_array($tipo, Config::TIPOS_DESPESA) && $valor >= 0) {
                $despesaModel = new Despesa();
                $prestacaoModel = new Prestacao();

                $userId = Auth::isAdmin() ? null : Auth::userId();
                $p_id = $despesaModel->getPrestacaoIdDaDespesa($id, $userId);

                if ($p_id !== false) {
                    $bloqueado = false;
                    if ($p_id) {
                        $prestacao = $prestacaoModel->getById($p_id, $userId);
                        if ($prestacao && in_array($prestacao['status'], ['PAGO', 'CANCELADO'])) {
                            $bloqueado = true;
                        }
                    }

                    if (!$bloqueado) {
                        $despesaModel->atualizar($id, $_POST['data'], $tipo, $valor, $userId);
                    }
                }
            }
        }
        $this->redirect($this->cleanUrl($_SERVER['HTTP_REFERER'] ?? ''));
    }

    public function mudarStatus() {
        if (isset($_POST['novo_status'], $_POST['id'])) {
            $st = $_POST['novo_status'];
            $status_validos = ['FALTA ENVIAR', 'EM CONFERÊNCIA', 'AG PAGAMENTO', 'REJEITADO', 'PAGO', 'CANCELADO'];
            if (in_array($st, $status_validos)) {
                $prestacaoModel = new Prestacao();
                $userId = Auth::isAdmin() ? null : Auth::userId();
                $prestacaoModel->mudarStatus($_POST['id'], $st, $userId);
            }
        }
        $this->redirect($this->cleanUrl($_SERVER['HTTP_REFERER'] ?? ''));
    }

    public function removerItem() {
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $despesaModel = new Despesa();
            $prestacaoModel = new Prestacao();

            $userId = Auth::isAdmin() ? null : Auth::userId();
            $p_id = $despesaModel->getPrestacaoIdDaDespesa($id, $userId);

            if ($p_id !== false) {
                $bloqueado = false;
                if ($p_id) {
                    $prestacao = $prestacaoModel->getById($p_id, $userId);
                    if ($prestacao && in_array($prestacao['status'], ['PAGO', 'CANCELADO'])) {
                        $bloqueado = true;
                    }
                }

                if (!$bloqueado) {
                    $despesaModel->removerDaPrestacao($id, $userId);
                }
            }
        }
        $this->redirect($this->cleanUrl($_SERVER['HTTP_REFERER'] ?? ''));
    }

    // ========================
    // Soft Delete Methods
    // ========================

    public function excluirItem() {
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $despesaModel = new Despesa();

            $userId = Auth::isAdmin() ? null : Auth::userId();
            $p_id = $despesaModel->getPrestacaoIdDaDespesa($id, $userId);

            // Só permite soft delete se não estiver vinculado a prestação
            if ($p_id !== false && !$p_id) {
                $despesaModel->softDelete($id, $userId);
            }
        }
        $this->redirect($this->cleanUrl($_SERVER['HTTP_REFERER'] ?? ''));
    }

    public function restaurarItem() {
        if (!Auth::isAdmin()) return;
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $despesaModel = new Despesa();
            $despesaModel->restore($id);
        }
        $this->redirect($this->cleanUrl($_SERVER['HTTP_REFERER'] ?? ''));
    }

    public function excluirPermanente() {
        if (!Auth::isAdmin()) return;
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $despesaModel = new Despesa();
            $despesaModel->hardDelete($id);
        }
        $this->redirect($this->cleanUrl($_SERVER['HTTP_REFERER'] ?? ''));
    }

    public function getDeletadas() {
        $this->checkAdmin();
        $despesaModel = new Despesa();
        $itens = $despesaModel->getDeletadas();
        $this->partial('partials/listagem_deletadas', [
            'itens' => $itens,
            'csrf_token' => \App\Core\Security::generateCsrf()
        ]);
    }

    // Ajax endpoints
    public function getPendentes() {
        $data = Security::sanitize($_GET['data'] ?? '');
        $tipo = Security::sanitize($_GET['tipo'] ?? '');
        $uid = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : Auth::userId();

        if (!Auth::isAdmin()) {
            $uid = Auth::userId();
        }

        $despesaModel = new Despesa();
        $itens = $despesaModel->getPendentesDetalhes($data, $tipo, $uid);

        $this->partial('partials/listagem_pendentes', ['itens' => $itens]);
    }

    public function getGrupos() {
        $id = (int)($_GET['id'] ?? 0);
        $despesaModel = new Despesa();
        $userId = Auth::isAdmin() ? null : Auth::userId();

        $grupos = $despesaModel->getResumoPorPrestacao($id, $userId);

        $this->partial('partials/listagem_grupos', ['grupos' => $grupos, 'prestacao_id' => $id]);
    }

    public function getItensPrestacao() {
        $id = (int)($_GET['id'] ?? 0);
        $data = Security::sanitize($_GET['data'] ?? '');
        $tipo = Security::sanitize($_GET['tipo'] ?? '');

        $prestacaoModel = new Prestacao();
        $despesaModel = new Despesa();
        $userId = Auth::isAdmin() ? null : Auth::userId();

        $prestacao = $prestacaoModel->getById($id, $userId);
        $bloqueado = $prestacao ? in_array($prestacao['status'], ['PAGO', 'CANCELADO']) : true;

        $itens = $despesaModel->getDetalhesPorPrestacaoDataTipo($id, $data, $tipo, $userId);

        $this->partial('partials/listagem_itens_prestacao', [
            'itens' => $itens,
            'bloqueado' => $bloqueado,
            'prestacao_id' => $id,
            'csrf_token' => Security::generateCsrf()
        ]);
    }
}
