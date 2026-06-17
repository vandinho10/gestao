<table class="table table-sm table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th>Total (c/ Teto)</th>
            <th class="text-end">Ação</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $total_geral_exibido = 0;
        $total_geral_real = 0;

        foreach ($grupos as $d):
            $real = $d['total_dia_tipo'];
            $exibido = ($d['tipo'] == 'Refeição') ? min($real, \App\Core\Config::TETO_REFEICAO) : $real;

            $total_geral_real += $real;
            $total_geral_exibido += $exibido;

            $alerta = "";
            if ($real > $exibido) {
                $excedente = number_format($real - $exibido, 2, ',', '.');
                $real_fmt = number_format($real, 2, ',', '.');
                $alerta = " <span class='badge bg-danger' title='Real: R$ {$real_fmt}'>⚠️ +{$excedente}</span>";
            }

            $dt_fmt = date('d/m/Y', strtotime($d['data_despesa']));
            $exib_fmt = number_format($exibido, 2, ',', '.');
        ?>
        <tr>
            <td><?= $dt_fmt ?></td>
            <td><span class='badge bg-secondary'><?= $d['tipo'] ?></span></td>
            <td><span class='fw-bold'>R$ <?= $exib_fmt ?></span><?= $alerta ?></td>
            <td class='text-end'>
                <button class='btn btn-xs btn-primary' onclick="abrirItensDataTipo(<?= $prestacao_id ?>, '<?= $d['data_despesa'] ?>', '<?= $d['tipo'] ?>')">Ver Notas</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class='table-light'>
        <tr>
            <th colspan='2' class='text-end'>Total Geral:</th>
            <th colspan='2' class='text-success fw-bold'>
                R$ <?= number_format($total_geral_exibido, 2, ',', '.') ?>
                <?php if ($total_geral_real > $total_geral_exibido): ?>
                    <br><small class='text-muted'>Real gasto: R$ <?= number_format($total_geral_real, 2, ',', '.') ?></small>
                <?php endif; ?>
            </th>
        </tr>
    </tfoot>
</table>