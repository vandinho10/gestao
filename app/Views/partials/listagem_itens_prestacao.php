<div class="mb-3">
    <button class="btn btn-sm btn-outline-secondary" onclick="abrirListagemPrestacao(<?= $prestacao_id ?>)">← Voltar</button>
</div>
<table class="table table-sm table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Valor Original</th>
            <th class="text-end">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $total = 0;
        foreach ($itens as $d):
            $total += $d['valor'];
            $valor = number_format($d['valor'], 2, ',', '.');
        ?>
        <tr>
            <td>#<?= $d['id'] ?></td>
            <td class='fw-bold'>R$ <?= $valor ?></td>
            <td class='text-end'>
                <?php if (!$bloqueado): ?>
                    <button class='btn btn-xs btn-warning' onclick="editarNota(<?= $d['id'] ?>, '<?= $d['data_despesa'] ?>', '<?= $d['tipo'] ?>', '<?= $d['valor'] ?>')">Editar</button>
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
            <th>Total:</th>
            <th class="fw-bold text-success">R$ <?= number_format($total, 2, ',', '.') ?></th>
            <th></th>
        </tr>
    </tfoot>
</table>