<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Gestão de Prestação de Contas') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>.btn-xs { padding: 1px 5px; font-size: 11px; } .table-middle td { vertical-align: middle; } .card-header { font-weight: bold; }</style>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Sistema de Prestação de Contas<?= $isAdmin && basename($_SERVER['REQUEST_URI']) == 'admin' ? ' - <span class="text-warning">Painel Administrador</span>' : '' ?></span>
        <div class="d-flex align-items-center">
            <?php if (isset($usuario) && $usuario): ?>
                <span class="text-light me-3 d-none d-sm-inline">Olá, <strong><?= htmlspecialchars($usuario['name']) ?></strong></span>
            <?php endif; ?>

            <?php if ($isAdmin && strpos($_SERVER['REQUEST_URI'], 'admin') === false): ?>
                <a href="<?= \App\Core\Config::BASE_URL ?>admin" class="btn btn-outline-warning btn-sm me-2">Painel Admin</a>
            <?php elseif ($isAdmin): ?>
                <a href="<?= \App\Core\Config::BASE_URL ?>" class="btn btn-outline-info btn-sm me-2">Voltar ao Meu Painel</a>
            <?php endif; ?>

            <button class="btn btn-outline-secondary btn-sm me-2" id="theme-toggle" title="Alternar Tema">🌓</button>
            <a href="<?= \App\Core\Config::BASE_URL ?>logout" class="btn btn-outline-light btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <?php require_once __DIR__ . '/' . $view . '.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/modais.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const baseUrl = '<?= \App\Core\Config::BASE_URL ?>';
    const modalListagem = new bootstrap.Modal(document.getElementById('modalListagem'));
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditar'));

    if (document.getElementById('selectAll')) {
        document.getElementById('selectAll').onclick = function() { document.querySelectorAll('.chkItem').forEach(c => c.checked = this.checked); }
    }

    function abrirListagemPendentes(data, tipo, userId = null) {
        let title = `Pendentes: ${tipo} (${data})`;
        if (userId) title += ` [User: ${userId}]`;
        document.getElementById('listagemTitulo').innerText = title;

        let url = `${baseUrl}acoes/getPendentes?data=${data}&tipo=${tipo}`;
        if (userId) url += `&usuario_id=${userId}`;

        fetch(url).then(r => r.text()).then(html => {
            document.getElementById('listagemCorpo').innerHTML = html; modalListagem.show();
        });
    }

    function abrirListagemPrestacao(id, numero) {
        if(numero) window.tempNum = numero;
        document.getElementById('listagemTitulo').innerText = `Prestação: ${window.tempNum}`;
        fetch(`${baseUrl}acoes/getGrupos?id=${id}`).then(r => r.text()).then(html => {
            document.getElementById('listagemCorpo').innerHTML = html; modalListagem.show();
        });
    }

    function abrirItensDataTipo(id, data, tipo) {
        document.getElementById('listagemTitulo').innerText = `${tipo} - ${data}`;
        fetch(`${baseUrl}acoes/getItensPrestacao?id=${id}&data=${data}&tipo=${tipo}`).then(r => r.text()).then(html => {
            document.getElementById('listagemCorpo').innerHTML = html;
        });
    }

    function editarNota(id, data, tipo, valor) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_data').value = data;
        document.getElementById('edit_tipo').value = tipo;
        document.getElementById('edit_valor').value = valor;
        modalListagem.hide();
        modalEditar.show();
    }

    document.getElementById('theme-toggle').addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
</script>
</body>
</html>