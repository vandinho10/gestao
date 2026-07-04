<?php
namespace App\Core;

class UploadHandler {
    const TIPOS_PERMITIDOS = ['pdf', 'jpeg', 'jpg', 'png'];
    const MAX_SIZE = 20 * 1024 * 1024; // 20MB
    const MAX_LARGURA = 1920;
    const MAX_ALTURA = 1920;
    const QUALIDADE_JPEG = 75;

    /**
     * Processa o upload, converte para JPEG e retorna array com dados do comprovante
     * @return array ['blob' => string, 'tipo_original' => string] | null
     */
    public static function processar($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) {
            return null;
        }

        if ($file['size'] > self::MAX_SIZE) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::TIPOS_PERMITIDOS)) {
            return null;
        }

        // Validação real de MIME type usando finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mime, $allowed_mimes)) {
            return null;
        }

        $jpegData = self::converterParaJpeg($file['tmp_name'], $ext);
        if ($jpegData === null) {
            return null;
        }

        return [
            'blob' => $jpegData,
            'tipo_original' => $ext
        ];
    }

    /**
     * Redimensiona a imagem para caber dentro dos limites máximos
     */
    private static function redimensionar($imagem) {
        $largura = imagesx($imagem);
        $altura = imagesy($imagem);

        // Se já está dentro dos limites, retorna original
        if ($largura <= self::MAX_LARGURA && $altura <= self::MAX_ALTURA) {
            return $imagem;
        }

        // Calcula proporção
        $ratio = min(self::MAX_LARGURA / $largura, self::MAX_ALTURA / $altura);
        $novaLargura = (int)round($largura * $ratio);
        $novaAltura = (int)round($altura * $ratio);

        $novaImagem = imagecreatetruecolor($novaLargura, $novaAltura);
        imagecopyresampled($novaImagem, $imagem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
        imagedestroy($imagem);

        return $novaImagem;
    }

    /**
     * Converte imagem/PDF para JPEG
     */
    private static function converterParaJpeg($caminho, $ext) {
        $imagem = null;

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $imagem = @imagecreatefromjpeg($caminho);
                break;
            case 'png':
                $imagem = @imagecreatefrompng($caminho);
                if ($imagem) {
                    // Preserva transparência com fundo branco
                    $bg = imagecreatetruecolor(imagesx($imagem), imagesy($imagem));
                    $white = imagecolorallocate($bg, 255, 255, 255);
                    imagefill($bg, 0, 0, $white);
                    imagecopy($bg, $imagem, 0, 0, 0, 0, imagesx($imagem), imagesy($imagem));
                    imagedestroy($imagem);
                    $imagem = $bg;
                }
                break;
            case 'pdf':
                // PDF não pode ser convertido sem Imagick/Ghostscript
                // Retorna o blob original do PDF
                $conteudo = file_get_contents($caminho);
                if ($conteudo === false) return null;
                return $conteudo;
            default:
                return null;
        }

        if (!$imagem) return null;

        // Redimensiona se necessário
        $imagem = self::redimensionar($imagem);

        ob_start();
        $success = imagejpeg($imagem, null, self::QUALIDADE_JPEG);
        $jpegData = ob_get_clean();
        imagedestroy($imagem);

        return $success ? $jpegData : null;
    }

    /**
     * Verifica se o arquivo é uma imagem (não PDF)
     */
    public static function isImagem($tipoOriginal) {
        return in_array($tipoOriginal, ['jpg', 'jpeg', 'png']);
    }
}