<div class="row">
    <!-- Listagens -->
    <div class="col-md-12">
        <!-- Pendentes -->
        <?php $this->partial('partials/tabela_pendentes', ['pendentes' => $pendentes, 'isAdminView' => true]); ?>

        <!-- Prestações Geradas -->
        <?php $this->partial('partials/tabela_prestacoes', ['prestacoes' => $prestacoes, 'exibir_todos' => $exibir_todos, 'isAdminView' => true]); ?>

        <!-- Notas Deletadas -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <span>Notas Deletadas</span>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="abrirDeletadas()">Ver Deletadas</button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirDeletadas() {
    document.getElementById('listagemTitulo').innerText = 'Notas Deletadas';
    fetch('<?= \App\Core\Config::BASE_URL ?>acoes/getDeletadas').then(r => r.text()).then(html => {
        document.getElementById('listagemCorpo').innerHTML = html;
        modalListagem.show();
    });
}
</script>
