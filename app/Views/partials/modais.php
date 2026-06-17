<!-- Modal Listagem -->
<div class="modal fade" id="modalListagem" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="listagemTitulo"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="listagemCorpo"></div>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Config::BASE_URL ?>acoes/editar" method="POST" class="modal-content shadow-lg">
            <div class="modal-header bg-warning fw-bold">Editar Nota</div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrf() ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-2">
                    <label>Data</label>
                    <input type="date" name="data" id="edit_data" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Tipo</label>
                    <select name="tipo" id="edit_tipo" class="form-select">
                        <?php foreach(\App\Core\Config::TIPOS_DESPESA as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Valor</label>
                    <input type="number" step="0.01" name="valor" id="edit_valor" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>