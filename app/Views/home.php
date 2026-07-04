<div class="row">
    <!-- Cadastro -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white" style="background-color: var(--bs-primary) !important;">Novo Lançamento</div>
            <div class="card-body">
                <form action="<?= \App\Core\Config::BASE_URL ?>acoes/lancar" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrf() ?>">
                    <div class="mb-2"><label class="small">Data</label><input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-2"><label class="small">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <?php foreach(\App\Core\Config::TIPOS_DESPESA as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="small">Valor (R$)</label><input type="number" step="0.01" name="valor" class="form-control" required></div>
                    <div class="mb-3"><label class="small">Comprovante (foto ou PDF)</label><input type="file" name="comprovante" id="comprovante" class="form-control form-control-sm" accept="image/*,.pdf" capture="environment"></div>
                    <input type="hidden" name="comprovante_base64" id="comprovante_base64">
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

<script>
document.getElementById('comprovante').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Se for PDF, envia normal (não comprime)
    if (file.type === 'application/pdf') {
        document.getElementById('comprovante_base64').value = '';
        return;
    }

    // Comprime imagem antes do upload
    const reader = new FileReader();
    reader.onload = function(ev) {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            let largura = img.width;
            let altura = img.height;

            // Redimensiona para no máximo 1920px no lado maior
            const MAX = 1920;
            if (largura > MAX || altura > MAX) {
                const ratio = Math.min(MAX / largura, MAX / altura);
                largura = Math.round(largura * ratio);
                altura = Math.round(altura * ratio);
            }

            canvas.width = largura;
            canvas.height = altura;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, largura, altura);

            // Converte para JPEG com qualidade 70%
            const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
            document.getElementById('comprovante_base64').value = dataUrl;
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});
</script>
