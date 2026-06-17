<?php if (empty($itens)): ?>
    <div class="alert alert-info mb-0">Nenhuma nota deletada encontrada.</div>
<?php else: ?>
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Usuário</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Excluída em</th>
                <th class="text-end">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $d):
                $valor = number_format($d['valor'], 2, ',', '.');
                $data_despesa = date('d/m/Y', strtotime($d['data_despesa']));
                $deleted_at = date('d/m/Y H:i', strtotime($d['deleted_at']));
            ?>
            <tr>
                <td><span class="badge bg-light text-dark border">User ID: <?= (int)$d['usuario_id'] ?></span></td>
                <td><?= $data_despesa ?></td>
                <td><span class="badge bg-secondary"><?= $d['tipo'] ?></span></td>
                <td class="fw-bold">R$ <?= $valor ?></td>
                <td><small class="text-muted"><?= $deleted_at ?></small></td>
                <td class="text-end">
                    <form action="<?= \App\Core\Config::BASE_URL ?>acoes/restaurarItem" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button type="submit" class="btn btn-xs btn-success" onclick="return confirm('Restaurar esta NF? Ela voltará a aparecer para o usuário.')">Restaurar</button>
                    </form>
                    <form action="<?= \App\Core\Config::BASE_URL ?>acoes/excluirPermanente" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Excluir PERMANENTEMENTE esta NF? Esta ação não pode ser desfeita.')">Excluir Permanentemente</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>