<div class="mb-3">
    <button class="btn btn-sm btn-outline-secondary" onclick="abrirListagemPrestacao(<?= $prestacao_id ?>)">← Voltar</button>
</div>
<table class="table table-sm table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Valor Original</th>
            <th>Comprovante</th>
            <th class="text-end">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $total = 0;
        $temComprovante = false;
        foreach ($itens as $d):
            $total += $d['valor'];
            $valor = number_format($d['valor'], 2, ',', '.');
            $temComprovante = $temComprovante || !empty($d['comprovante']);
        ?>
        <tr>
            <td>#<?= $d['id'] ?></td>
            <td class='fw-bold'>R$ <?= $valor ?></td>
            <td>
                <?php if (!empty($d['comprovante'])): ?>
                    <a href="<?= \App\Core\Config::BASE_URL ?>acoes/getComprovante?id=<?= $d['id'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="Visualizar Comprovante">👁️</a>
                    <a href="<?= \App\Core\Config::BASE_URL ?>acoes/download?tipo=jpeg&ids=<?= $d['id'] ?>" class="btn btn-xs btn-outline-primary" title="Download JPEG">🖼️</a>
                    <a href="<?= \App\Core\Config::BASE_URL ?>acoes/download?tipo=pdf&ids=<?= $d['id'] ?>" class="btn btn-xs btn-outline-primary" title="Download PDF">📄</a>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class='text-end'>
                <?php if (!$bloqueado): ?>
                    <button class='btn btn-xs btn-warning' onclick="editarNota(<?= $d['id'] ?>, '<?= \App\Core\Security::sanitize($d['data_despesa']) ?>', '<?= \App\Core\Security::sanitize($d['tipo']) ?>', '<?= $d['valor'] ?>')">Editar</button>
                    <form action='<?= \App\Core\Config::BASE_URL ?>acoes/removerItem' method='POST' class='d-inline' onsubmit='return confirm("Retirar nota?")'>
                        <input type='hidden' name='csrf_token' value='<?= $csrf_token ?>'>
                        <input type='hidden' name='id' value='<?= $d['id'] ?>'>
                        <button type='submit' class='btn btn-xs btn-danger'>Retirar</button>
                    </form>
                <?php else: ?>
                    <span class='badge bg-secondary' title='Bloqueado pelo status da prestação'>Bloqueado</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="table-light">
        <tr>
            <th colspan="4">
                <span class="fw-bold text-success">Total: R$ <?= number_format($total, 2, ',', '.') ?></span>
                <?php if ($temComprovante): ?>
                    <span class="float-end">
                        <a href="<?= \App\Core\Config::BASE_URL ?>acoes/download?tipo=pdfmulti&<?= isset($itens[0]['data_despesa']) ? 'grupo_data='.$itens[0]['data_despesa'].'&grupo_tipo='.$itens[0]['tipo'] : '' ?>" class="btn btn-sm btn-outline-dark">📥 Download Todos (PDF)</a>
                    </span>
                <?php endif; ?>
            </th>
        </tr>
    </tfoot>
</table>
