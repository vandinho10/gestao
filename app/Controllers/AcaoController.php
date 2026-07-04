<?php
namespace App\Controllers;
use App\Models\Despesa;
use App\Models\Prestacao;
use App\Core\Auth;
use App\Core\Security;
use App\Core\Config;
use App\Core\UploadHandler;
use App\Core\DownloadHandler;

class AcaoController extends BaseController {

    public function __construct() {
        $this->checkAuth();
        // Ações que alteram dados requerem POST e CSRF
        $acoes_get = ['getPendentes', 'getGrupos', 'getItensPrestacao', 'getDeletadas', 'getComprovante', 'download'];
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

                // Processa upload de comprovante
                $comprovante_blob = null;
                $comprovante_tipo = null;

                // Prioriza comprovante comprimido via base64 (frontend)
                if (!empty($_POST['comprovante_base64'])) {
                    $dados = self::processarBase64($_POST['comprovante_base64']);
                    if ($dados) {
                        $comprovante_blob = $dados['blob'];
                        $comprovante_tipo = $dados['tipo_original'];
                    }
                }

                // Fallback: upload tradicional
                if (!$comprovante_blob) {
                    $comprovante = UploadHandler::processar($_FILES['comprovante'] ?? null);
                    if ($comprovante) {
                        $comprovante_blob = $comprovante['blob'];
                        $comprovante_tipo = $comprovante['tipo_original'];
                    }
                }

                $model->criar(Auth::userId(), $_POST['data'], $tipo, $valor, $comprovante_blob, $comprovante_tipo);
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
                        // Processa upload de comprovante (se enviado)
                        $comprovante_blob = null;
                        $comprovante_tipo = null;

                        // Prioriza comprovante comprimido via base64 (frontend)
                        if (!empty($_POST['comprovante_base64'])) {
                            $dados = self::processarBase64($_POST['comprovante_base64']);
                            if ($dados) {
                                $comprovante_blob = $dados['blob'];
                                $comprovante_tipo = $dados['tipo_original'];
                            }
                        }

                        // Fallback: upload tradicional
                        if (!$comprovante_blob) {
                            $comprovante = UploadHandler::processar($_FILES['comprovante'] ?? null);
                            if ($comprovante) {
                                $comprovante_blob = $comprovante['blob'];
                                $comprovante_tipo = $comprovante['tipo_original'];
                            }
                        }

                        $despesaModel->atualizar($id, $_POST['data'], $tipo, $valor, $userId, $comprovante_blob, $comprovante_tipo);
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

    // ========================
    // Comprovante / Download
    // ========================

    /**
     * Serve o comprovante de uma despesa para visualização inline
     */
    public function getComprovante() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return;

        $despesaModel = new Despesa();
        $userId = Auth::isAdmin() ? null : Auth::userId();
        $stmt = \App\Core\Database::getInstance()->prepare("SELECT comprovante, comprovante_tipo FROM despesas WHERE id = ?" . ($userId ? " AND usuario_id = " . (int)$userId : ""));
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row || empty($row['comprovante'])) {
            header('HTTP/1.1 404 Not Found');
            echo 'Comprovante não encontrado.';
            exit;
        }

        $blob = $row['comprovante'];
        $tipo = $row['comprovante_tipo'];

        if (UploadHandler::isImagem($tipo)) {
            header('Content-Type: image/jpeg');
        } else {
            header('Content-Type: application/pdf');
        }
        header('Content-Length: ' . strlen($blob));
        echo $blob;
        exit;
    }

    /**
     * Endpoint de download unificado
     * Parâmetros:
     *   - tipo: jpeg | pdf | pdfmulti
     *   - ids: IDs das despesas separados por vírgula (para download unitário/individual)
     *   - grupo_data: data do grupo (para download agrupado)
     *   - grupo_tipo: tipo do grupo
     *   - grupo_ids: IDs do grupo (para download agrupado)
     */
    public function download() {
        $tipoDownload = Security::sanitize($_GET['tipo'] ?? 'jpeg');
        $ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : [];
        $grupoData = Security::sanitize($_GET['grupo_data'] ?? '');
        $grupoTipo = Security::sanitize($_GET['grupo_tipo'] ?? '');
        $grupoIds = isset($_GET['grupo_ids']) ? array_map('intval', explode(',', $_GET['grupo_ids'])) : [];

        $userId = Auth::isAdmin() ? null : Auth::userId();
        $despesaModel = new Despesa();

        // Se tem grupo_data e grupo_tipo, busca todas as despesas desse grupo
        if (!empty($grupoData) && !empty($grupoTipo)) {
            if (!empty($grupoIds)) {
                $itens = $this->buscarDespesasPorIds($grupoIds, $userId);
            } elseif (!empty($ids)) {
                $itens = $this->buscarDespesasPorIds($ids, $userId);
            } else {
                $itens = $despesaModel->getPendentesDetalhes($grupoData, $grupoTipo, $userId);
            }
        } elseif (!empty($ids)) {
            $itens = $this->buscarDespesasPorIds($ids, $userId);
        } else {
            header('HTTP/1.1 400 Bad Request');
            echo 'Nenhum item especificado.';
            exit;
        }

        if (empty($itens)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Nenhum item encontrado.';
            exit;
        }

        // Filtra apenas itens com comprovante
        $itensComComprovante = array_values(array_filter($itens, function($i) {
            return !empty($i['comprovante']);
        }));

        if (empty($itensComComprovante)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Nenhum comprovante encontrado para download.';
            exit;
        }

        if ($tipoDownload === 'pdfmulti' || (count($itensComComprovante) > 1 && $tipoDownload === 'pdf')) {
            // PDF Multi-páginas
            $pdfItens = [];
            foreach ($itensComComprovante as $item) {
                $pdfItens[] = [
                    'blob' => $item['comprovante'],
                    'data_despesa' => $item['data_despesa'],
                    'tipo' => $item['tipo'],
                    'id' => $item['id']
                ];
            }
            $result = DownloadHandler::gerarPdfMultiPaginas($pdfItens, true);
            if ($result) {
                DownloadHandler::servirPdf($result['pdf'], $result['nome']);
            }
        } elseif ($tipoDownload === 'pdf' && count($itensComComprovante) === 1) {
            // PDF Único
            $item = $itensComComprovante[0];
            if (UploadHandler::isImagem($item['comprovante_tipo'] ?? '')) {
                $result = DownloadHandler::gerarPdfUnico($item['comprovante'], $item['data_despesa'], $item['id'], $item['tipo']);
                if ($result) {
                    DownloadHandler::servirPdf($result['pdf'], $result['nome']);
                }
            } else {
                // Já é PDF
                $nome = DownloadHandler::gerarNomeArquivo($item['data_despesa'], $item['id'], $item['tipo'], false, 'pdf');
                DownloadHandler::servirPdf($item['comprovante'], $nome);
            }
        } else {
            // JPEG - apenas para o primeiro item
            $item = $itensComComprovante[0];
            if (UploadHandler::isImagem($item['comprovante_tipo'] ?? '')) {
                $nome = DownloadHandler::gerarNomeArquivo($item['data_despesa'], $item['id'], $item['tipo'], false, 'jpg');
                DownloadHandler::servirJpeg($item['comprovante'], $nome);
            } else {
                // É PDF, servir como PDF
                $nome = DownloadHandler::gerarNomeArquivo($item['data_despesa'], $item['id'], $item['tipo'], false, 'pdf');
                DownloadHandler::servirPdf($item['comprovante'], $nome);
            }
        }
    }

    /**
     * Processa dados de imagem em base64 (data URI) e retorna blob binário
     */
    private static function processarBase64($dataUri) {
        // Extrai os dados base64: "data:image/jpeg;base64,...."
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $dataUri, $matches)) {
            $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            if (!in_array($ext, UploadHandler::TIPOS_PERMITIDOS)) {
                return null;
            }
            $binario = base64_decode($matches[2], true);
            if ($binario === false) return null;

            return [
                'blob' => $binario,
                'tipo_original' => $ext
            ];
        }
        return null;
    }

    /**
     * Busca despesas por IDs com comprovante
     */
    private function buscarDespesasPorIds($ids, $userId = null) {
        if (empty($ids)) return [];
        $db = \App\Core\Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM despesas WHERE id IN (" . $placeholders . ") AND comprovante IS NOT NULL";
        $params = $ids;

        if ($userId !== null) {
            $sql .= " AND usuario_id = ?";
            $params[] = $userId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
