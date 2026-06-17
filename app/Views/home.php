<div class="row">
    <!-- Cadastro -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">Novo Lançamento</div>
            <div class="card-body">
                <form action="<?= \App\Core\Config::BASE_URL ?>acoes/lancar" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrf() ?>">
                    <div class="mb-2"><label class="small">Data</label><input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-2"><label class="small">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <?php foreach(\App\Core\Config::TIPOS_DESPESA as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="small">Valor (R$)</label><input type="number" step="0.01" name="valor" class="form-control" required></div>
                    <button type="submit" class="btn btn-success w-100">Salvar Item</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Listagens -->
    <div class="col-md-9">
        <!-- Pendentes -->
        <?php $this->partial('partials/tabela_pendentes', ['pendentes' => $pendentes, 'isAdminView' => false]); ?>

        <!-- Prestações Geradas -->
        <?php $this->partial('partials/tabela_prestacoes', ['prestacoes' => $prestacoes, 'exibir_todos' => $exibir_todos, 'isAdminView' => false]); ?>
    </div>
</div>