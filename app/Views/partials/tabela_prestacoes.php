<div class="card shadow-sm border-0">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <span>Resumo das Prestações <?= $isAdminView ? '(Todos os Usuários)' : '' ?></span>
        <a href="<?= $exibir_todos ? strtok($_SERVER['REQUEST_URI'], '?') : '?exibir_todos=1' ?>" class="btn btn-sm btn-outline-secondary">
            <?= $exibir_todos ? 'Ocultar Finalizados' : 'Exibir Todos' ?>
        </a>
    </div>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><?= $isAdminView ? '<th>Usuário</th>' : '' ?><th>Nº Lançamento</th><th>Total Geral c/ Teto</th><th>Status</th><th class="text-end">Ação</th></tr></thead>
        <tbody>
            <?php
            $somas_por_status = [];
            $total_geral_nao_pago = 0;

            foreach ($prestacoes as $p):
                $total_prestacao = (float)($p['total'] ?? 0);
                $menor_data = $p['menor_data'] ?? null;
                $maior_data = $p['maior_data'] ?? null;

                $periodo_txt = "";
                if ($menor_data && $maior_data) {
                    if ($menor_data === $maior_data) {
                        $periodo_txt = "<br><small class='text-muted fw-normal'>🗓 " . date('d/m/Y', strtotime($menor_data)) . "</small>";
                    } else {
                        $periodo_txt = "<br><small class='text-muted fw-normal'>🗓 " . date('d/m/Y', strtotime($menor_data)) . " a " . date('d/m/Y', strtotime($maior_data)) . "</small>";
                    }
                }

                $status_atual = $p['status'] ?? 'FALTA ENVIAR';
                if (!isset($somas_por_status[$status_atual])) {
                    $somas_por_status[$status_atual] = 0;
                }
                $somas_por_status[$status_atual] += $total_prestacao;

                if (!in_array($status_atual, ['PAGO', 'CANCELADO'])) {
                    $total_geral_nao_pago += $total_prestacao;
                }
            ?>
            <tr>
                <?php if ($isAdminView): ?>
                    <td><span class="badge bg-light text-dark border">User ID: <?= $p['usuario_id'] ?: 'Desconhecido' ?></span></td>
                <?php endif; ?>
                <td class="fw-bold">
                    <?= htmlspecialchars($p['numero_externo']) ?>
                    <?= $periodo_txt ?>
                </td>
                <td class="text-success fw-bold">R$ <?= number_format($total_prestacao, 2, ',', '.') ?></td>
                <td>
                    <?php if (in_array($status_atual, ['PAGO', 'CANCELADO'])): ?>
                        <span class="badge <?= $status_atual == 'PAGO' ? 'bg-success' : 'bg-secondary' ?> fs-6"><?= $status_atual ?></span>
                    <?php else: ?>
                    <form action="<?= \App\Core\Config::BASE_URL ?>acoes/mudarStatus" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrf() ?>">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <select name="novo_status" onchange="this.form.submit()" class="form-select form-select-sm w-auto d-inline">
                            <?php foreach(['FALTA ENVIAR', 'EM CONFERÊNCIA', 'AG PAGAMENTO', 'REJEITADO', 'PAGO', 'CANCELADO'] as $st): ?>
                                <option value="<?= $st ?>" <?= $status_atual==$st?'selected':'' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php endif; ?>
                </td>
                <td class="text-end"><button onclick="abrirListagemPrestacao(<?= $p['id'] ?>, '<?= htmlspecialchars($p['numero_externo']) ?>')" class="btn btn-sm btn-dark">Ver Detalhes</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <?php foreach(['FALTA ENVIAR', 'EM CONFERÊNCIA', 'AG PAGAMENTO'] as $st):
                if (isset($somas_por_status[$st]) && $somas_por_status[$st] > 0): ?>
                <tr>
                    <td colspan="<?= $isAdminView ? '5' : '4' ?>" class="text-end text-muted small border-0 py-1">
                        Soma <strong><?= $st ?></strong>: R$ <?= number_format($somas_por_status[$st], 2, ',', '.') ?>
                    </td>
                </tr>
            <?php endif; endforeach; ?>
            <tr>
                <th colspan="<?= $isAdminView ? '3' : '2' ?>" class="text-end border-top">Total Pendente de Pagamento:</th>
                <th colspan="2" class="text-primary fw-bold fs-5 border-top">R$ <?= number_format($total_geral_nao_pago, 2, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>
</div>