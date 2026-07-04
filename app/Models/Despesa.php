<?php
namespace App\Models;
use App\Core\Database;
use App\Core\Config;

class Despesa {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function criar($usuario_id, $data, $tipo, $valor, $comprovante_blob = null, $comprovante_tipo = null) {
        if ($comprovante_blob !== null) {
            $stmt = $this->db->prepare("INSERT INTO despesas (usuario_id, data_despesa, tipo, valor, comprovante, comprovante_tipo) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$usuario_id, $data, $tipo, $valor, $comprovante_blob, $comprovante_tipo]);
        }
        $stmt = $this->db->prepare("INSERT INTO despesas (usuario_id, data_despesa, tipo, valor) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$usuario_id, $data, $tipo, $valor]);
    }

    public function atualizar($id, $data, $tipo, $valor, $usuario_id = null, $comprovante_blob = null, $comprovante_tipo = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";

        if ($comprovante_blob !== null) {
            $stmt = $this->db->prepare("UPDATE despesas SET data_despesa = ?, tipo = ?, valor = ?, comprovante = ?, comprovante_tipo = ? WHERE id = ?" . $filtro);
            return $stmt->execute([$data, $tipo, $valor, $comprovante_blob, $comprovante_tipo, $id]);
        }

        $stmt = $this->db->prepare("UPDATE despesas SET data_despesa = ?, tipo = ?, valor = ? WHERE id = ?" . $filtro);
        return $stmt->execute([$data, $tipo, $valor, $id]);
    }

    public function removerDaPrestacao($id, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("UPDATE despesas SET prestacao_id = NULL WHERE id = ?" . $filtro);
        return $stmt->execute([$id]);
    }

    public function getPrestacaoIdDaDespesa($id, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("SELECT prestacao_id FROM despesas WHERE id = ?" . $filtro);
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

    public function getPendentesAgrupadas($usuario_id = null) {
        if ($usuario_id) {
            $sql = "SELECT data_despesa, tipo, SUM(valor) as total, COUNT(*) as qtd, GROUP_CONCAT(id) as ids
                    FROM despesas WHERE prestacao_id IS NULL AND deleted_at IS NULL AND usuario_id = ?
                    GROUP BY data_despesa, tipo ORDER BY data_despesa ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id]);
        } else {
            $sql = "SELECT usuario_id, data_despesa, tipo, SUM(valor) as total, COUNT(*) as qtd, GROUP_CONCAT(id) as ids
                    FROM despesas WHERE prestacao_id IS NULL AND deleted_at IS NULL
                    GROUP BY usuario_id, data_despesa, tipo ORDER BY usuario_id, data_despesa ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public function getPendentesDetalhes($data, $tipo, $usuario_id = null) {
        $sql = "SELECT * FROM despesas WHERE data_despesa = ? AND tipo = ? AND prestacao_id IS NULL AND deleted_at IS NULL";
        $params = [$data, $tipo];

        if ($usuario_id !== null) {
            $sql .= " AND usuario_id = ?";
            $params[] = $usuario_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDetalhesPorPrestacaoDataTipo($prestacao_id, $data, $tipo, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("SELECT * FROM despesas WHERE prestacao_id = ? AND data_despesa = ? AND tipo = ?" . $filtro);
        $stmt->execute([$prestacao_id, $data, $tipo]);
        return $stmt->fetchAll();
    }

    public function getResumoPorPrestacao($prestacao_id, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("SELECT data_despesa, tipo, SUM(valor) as total_dia_tipo, COUNT(*) as qtd
                                FROM despesas WHERE prestacao_id = ?" . $filtro . "
                                GROUP BY data_despesa, tipo ORDER BY data_despesa DESC, tipo ASC");
        $stmt->execute([$prestacao_id]);
        return $stmt->fetchAll();
    }

    // ========================
    // Soft Delete Methods
    // ========================

    public function softDelete($id, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("UPDATE despesas SET deleted_at = NOW() WHERE id = ? AND prestacao_id IS NULL" . $filtro);
        return $stmt->execute([$id]);
    }

    public function restore($id) {
        $stmt = $this->db->prepare("UPDATE despesas SET deleted_at = NULL WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function hardDelete($id) {
        $stmt = $this->db->prepare("DELETE FROM despesas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getDeletadas() {
        $stmt = $this->db->query("
            SELECT d.*
            FROM despesas d
            WHERE d.deleted_at IS NOT NULL
            ORDER BY d.deleted_at DESC
        ");
        return $stmt->fetchAll();
    }
}