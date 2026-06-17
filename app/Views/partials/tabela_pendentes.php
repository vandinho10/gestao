<div class="card shadow-sm border-0 mb-4">
    <div class="card-header border-bottom">Itens Pendentes <?= $isAdminView ? '(Visão Geral - Todos os Usuários)' : '' ?></div>
    <div class="card-body">
        <?php if (isset($_GET['aviso']) && $_GET['aviso'] == 'duplicado'): ?>
            <div class="alert alert-warning mb-3">
                O número de lançamento <strong><?= htmlspecialchars($_GET['numero'] ?? '') ?></strong> já existe.
                <form action="<?= \App\Core\Config::BASE_URL ?>acoes/agrupar?confirmado=1" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrf() ?>">
                    <input type="hidden" name="numero_prestacao" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
                    <?php
                    $itens = isset($_GET['itens']) ? explode(',', $_GET['itens']) : [];
                    foreach($itens as $item):
                    ?>
                        <input type="hidden" name="itens_diretos[]" value="<?= htmlspecialchars($item) ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-sm btn-warning ms-2 fw-bold">Adicionar a este Grupo mesmo assim</button>
                </form>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['aviso']) && $_GET['aviso'] == 'bloqueado_agrupar'): ?>
            <div class="alert alert-danger mb-3">
                <strong>Ação Bloqueada:</strong> O lançamento informado já se encontra <strong>PAGO</strong> ou <strong>CANCELADO</strong>. Não é permitido adicionar novas notas a ele.
            </div>
        <?php endif; ?>
        <form action="<?= \App\Core\Config::BASE_URL ?>acoes/agrupar" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrf() ?>">
            <div class="row g-2 mb-3 align-items-end">
                <div class="col-md-<?= $isAdminView ? '3' : '4' ?>"><input type="text" name="numero_prestacao" class="form-control" placeholder="Nº Lançamento Empresa" required></div>
                <div class="col-md-<?= $isAdminView ? '2' : '3' ?>"><button class="btn btn-primary w-100">Agrupar Selecionados</button></div>
                <?php if ($isAdminView): ?>
                    <div class="col-md-7 text-end text-muted small pb-2">
                        * Como admin, tenha certeza de agrupar apenas notas do mesmo usuário em uma única prestação!
                    </div>
                <?php endif; ?>
            </div>
            <table class="table table-sm table-hover table-middle">
                <thead class="table-light"><tr><th width="30"><input type="checkbox" id="selectAll"></th><?= $isAdminView ? '<th>Usuário</th>' : '' ?><th>Data</th><th>Tipo</th><th>Total c/ Teto</th><th class="text-end">Ação</th></tr></thead>
                <tbody>
                    <?php
                    $soma_pendentes_exibido = 0;
                    $soma_pendentes_real = 0;
                    foreach ($pendentes as $g):
                        $real = $g['total'];
                        $exibido = ($g['tipo'] == 'Refeição') ? min($real, \App\Core\Config::TETO_REFEICAO) : $real;
                        $soma_pendentes_exibido += $exibido;
                        $soma_pendentes_real += $real;
                        $alerta = ($real > $exibido) ? " <span class='badge bg-danger' title='Real: R$ ".number_format($real,2,',','.')."'>⚠️ Limite</span>" : "";
                    ?>
                    <tr>
                        <td><input type="checkbox" name="grupos_ids[]" value="<?= $g['ids'] ?>" class="chkItem"></td>
                        <?php if ($isAdminView): ?>
                            <td><span class="badge bg-light text-dark border">User ID: <?= $g['usuario_id'] ?: 'Desconhecido' ?></span></td>
                        <?php endif; ?>
                        <td><?= date('d/m/Y', strtotime($g['data_despesa'])) ?></td>
                        <td><span class="badge bg-secondary"><?= $g['tipo'] ?></span></td>
                        <td><span class="fw-bold">R$ <?= number_format($exibido, 2, ',', '.') ?></span><?= $alerta ?></td>
                        <td class="text-end"><button type="button" class="btn btn-xs btn-outline-info" onclick="abrirListagemPendentes('<?= $g['data_despesa'] ?>', '<?= $g['tipo'] ?>'<?= $isAdminView ? ", '{$g['usuario_id']}'" : '' ?>)">Ver Notas</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="<?= $isAdminView ? '4' : '3' ?>" class="text-end">Total Pendentes:</th>
                        <th colspan="2" class="text-success fw-bold">
                            R$ <?= number_format($soma_pendentes_exibido, 2, ',', '.') ?>
                            <?php if($soma_pendentes_real > $soma_pendentes_exibido): ?>
                                <br><small class="text-muted fw-normal">Real acumulado: R$ <?= number_format($soma_pendentes_real, 2, ',', '.') ?></small>
                            <?php endif; ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>
</div>