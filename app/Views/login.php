<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão de Contas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: var(--bs-secondary-bg); }
        .login-container { margin-top: 10%; }
        .brand-icon { font-size: 3rem; color: var(--bs-primary); margin-bottom: 1rem; }
        #theme-toggle { position: absolute; top: 1rem; right: 1rem; }
    </style>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
</head>
<body>
    <button class="btn btn-outline-secondary btn-sm" id="theme-toggle" title="Alternar Tema">🌓</button>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4 col-sm-8 col-11">
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center brand-icon">📊</div>
                        <h3 class="text-center mb-4 fw-bold">Gestão de Contas</h3>

                        <?php if($erro): ?>
                            <div class="alert alert-danger text-center p-2 mb-4" role="alert">
                                <?= htmlspecialchars($erro) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= \App\Core\Config::BASE_URL ?>login">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Nome de Usuário</label>
                                <input type="text" name="username" class="form-control form-control-lg" required autofocus placeholder="Digite seu usuário">
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted fw-semibold">Senha</label>
                                <input type="password" name="password" class="form-control form-control-lg" required placeholder="Digite sua senha">
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">Entrar no Sistema</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    </script>
</body>
</html>