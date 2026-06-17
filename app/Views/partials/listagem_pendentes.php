<table class="table table-sm table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Valor Original</th>
            <th class="text-end">Ação</th>
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
            <td class='fw-bold'>R$ <?= $valor ?></td>
            <td class='text-end'>
                <button class='btn btn-xs btn-warning' onclick="editarNota(<?= $d['id'] ?>, '<?= $d['data_despesa'] ?>', '<?= $d['tipo'] ?>', '<?= $d['valor'] ?>')">Editar</button>
                <form action='<?= \App\Core\Config::BASE_URL ?>acoes/excluirItem' method='POST' class='d-inline' onsubmit="return confirm('Tem certeza que deseja excluir esta NF? Ela ficará oculta e poderá ser restaurada pelo administrador.')">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrf() ?>">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-danger">Excluir</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="table-light">
        <tr>
            <th class="fw-bold text-success">Total: R$ <?= number_format($total, 2, ',', '.') ?></th>
            <th></th>
        </tr>
    </tfoot>
</table>