<?php
namespace App\Models;
use App\Core\Database;
use App\Core\Config;

class Prestacao {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Retorna todas as prestações com totais calculados via JOIN + GROUP BY.
     * Elimina o problema N+1 da view tabela_prestacoes.php.
     */
    public function getTodasComTotais($exibir_todos = false, $usuario_id = null) {
        $teto = (float)\App\Core\Config::TETO_REFEICAO;
        $sql = "SELECT p.*,
                       COALESCE(SUM(CASE WHEN d.tipo = 'Refeição' THEN LEAST(d.valor, $teto) ELSE d.valor END), 0) as total,
                       MIN(d.data_despesa) as menor_data,
                       MAX(d.data_despesa) as maior_data
                FROM prestacoes p
                LEFT JOIN despesas d ON d.prestacao_id = p.id AND d.deleted_at IS NULL";
        $params = [];
        $conds = [];

        if (!$exibir_todos) {
            $conds[] = "(p.status NOT IN ('PAGO', 'CANCELADO') OR p.status IS NULL)";
        }

        if ($usuario_id !== null) {
            $conds[] = "p.usuario_id = ?";
            $params[] = $usuario_id;
        }

        if (count($conds) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conds);
        }

        $sql .= " GROUP BY p.id";
        $sql .= " ORDER BY CASE WHEN p.status IN ('PAGO', 'CANCELADO') THEN 1 ELSE 0 END, p.numero_externo DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTodas($exibir_todos = false, $usuario_id = null) {
        $sql = "SELECT * FROM prestacoes";
        $params = [];
        $conds = [];

        if (!$exibir_todos) {
            $conds[] = "(status NOT IN ('PAGO', 'CANCELADO') OR status IS NULL)";
        }

        if ($usuario_id !== null) {
            $conds[] = "usuario_id = ?";
            $params[] = $usuario_id;
        }

        if (count($conds) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conds);
        }

        $sql .= " ORDER BY CASE WHEN status IN ('PAGO', 'CANCELADO') THEN 1 ELSE 0 END, numero_externo DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("SELECT * FROM prestacoes WHERE id = ?" . $filtro);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByNumero($numero, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("SELECT id, status FROM prestacoes WHERE numero_externo = ?" . $filtro);
        $stmt->execute([$numero]);
        return $stmt->fetch();
    }

    public function mudarStatus($id, $novo_status, $usuario_id = null) {
        $filtro = $usuario_id ? " AND usuario_id = " . (int)$usuario_id : "";
        $stmt = $this->db->prepare("UPDATE prestacoes SET status = ? WHERE id = ?" . $filtro);
        return $stmt->execute([$novo_status, $id]);
    }

    public function agrupar($numero, $todos_ids, $usuario_id, $isAdmin) {
        // Find existing
        $filtro = $isAdmin ? "" : " AND usuario_id = " . (int)$usuario_id;
        $check = $this->db->prepare("SELECT id, status FROM prestacoes WHERE numero_externo = ?" . $filtro);
        $check->execute([$numero]);
        $prestacao = $check->fetch();

        try {
            $this->db->beginTransaction();
            if ($prestacao) {
                $prestacao_id = $prestacao['id'];
            } else {
                $stmt = $this->db->prepare("INSERT INTO prestacoes (usuario_id, numero_externo) VALUES (?, ?)");
                $stmt->execute([$usuario_id, $numero]);
                $prestacao_id = $this->db->lastInsertId();
            }

            // Bind in Despesas
            $placeholders = implode(',', array_fill(0, count($todos_ids), '?'));
            $sql = "UPDATE despesas SET prestacao_id = ? WHERE id IN (" . $placeholders . ")" . $filtro;

            $params = [$prestacao_id];
            foreach ($todos_ids as $id) {
                $params[] = (int)$id;
            }

            $this->db->prepare($sql)->execute($params);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
