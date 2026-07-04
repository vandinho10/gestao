<?php
namespace App\Core;

class DownloadHandler {

    /**
     * Gera nome do arquivo conforme regra 3.2
     * Unitário: yymmdd-xxxx-tipo.ext
     * Agrupado: yymmdd-tipo.pdf
     */
    public static function gerarNomeArquivo($data, $sequencial, $tipo, $isAgrupado = false, $ext = 'jpg') {
        $dataFormatada = date('ymd', strtotime($data));
        $sufixoTipo = ($tipo !== 'Refeição') ? '-' . strtolower($tipo) : '';

        if ($isAgrupado) {
            return $dataFormatada . $sufixoTipo . '.pdf';
        }

        $seq = str_pad($sequencial, 4, '0', STR_PAD_LEFT);
        return $dataFormatada . '-' . $seq . $sufixoTipo . '.' . $ext;
    }

    /**
     * Serve um JPEG do BLOB para download
     */
    public static function servirJpeg($blob, $nomeArquivo) {
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($blob));
        echo $blob;
        exit;
    }

    /**
     * Serve um PDF do BLOB para download
     */
    public static function servirPdf($blob, $nomeArquivo) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($blob));
        echo $blob;
        exit;
    }

    /**
     * Gera PDF com múltiplas páginas a partir de múltiplos comprovantes JPEG
     * @param array $itens Array de ['blob' => string, 'data_despesa' => string, 'tipo' => string, 'id' => int]
     * @param bool $isAgrupado Se true, nome agrupado (usa a primeira data)
     * @return array ['pdf' => string, 'nome' => string]
     */
    public static function gerarPdfMultiPaginas($itens, $isAgrupado = false) {
        require_once __DIR__ . '/fpdf/fpdf.php';

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 10);

        $primeiraData = null;
        $primeiroTipo = null;

        foreach ($itens as $item) {
            if ($primeiraData === null) $primeiraData = $item['data_despesa'];
            if ($primeiroTipo === null) $primeiroTipo = $item['tipo'];

            $blob = $item['blob'];
            if (empty($blob)) continue;

            // Salva blob temporário para o FPDF (com extensão .jpg para o FPDF reconhecer)
            $tmpFile = tempnam(sys_get_temp_dir(), 'cmp_') . '.jpg';
            file_put_contents($tmpFile, $blob);

            // Verifica se é JPEG válido
            $info = @getimagesize($tmpFile);
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $pdf->AddPage();
                // Redimensiona para caber na página A4 (210x297mm) com margens
                $wPdf = 190; // largura útil mm
                $hPdf = 277; // altura útil mm
                list($wImg, $hImg) = $info;

                $ratio = min($wPdf / $wImg, $hPdf / $hImg);
                $wFinal = $wImg * $ratio;
                $hFinal = $hImg * $ratio;

                $x = (210 - $wFinal) / 2;
                $y = (297 - $hFinal) / 2;

                $pdf->Image($tmpFile, $x, $y, $wFinal, $hFinal);
            }
            unlink($tmpFile);
        }

        if ($pdf->pageNo() === 0) return null;

        $nome = self::gerarNomeArquivo($primeiraData, 0, $primeiroTipo, $isAgrupado, 'pdf');

        return [
            'pdf' => $pdf->Output('S'),
            'nome' => $nome
        ];
    }

    /**
     * Gera PDF único a partir de um único comprovante
     */
    public static function gerarPdfUnico($blob, $data, $sequencial, $tipo) {
        $result = self::gerarPdfMultiPaginas([
            ['blob' => $blob, 'data_despesa' => $data, 'tipo' => $tipo, 'id' => $sequencial]
        ], false);

        if ($result) {
            $result['nome'] = self::gerarNomeArquivo($data, $sequencial, $tipo, false, 'pdf');
        }

        return $result;
    }
}