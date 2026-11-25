<?php

declare(strict_types=1);

namespace common\components;

use Yii;

/**
 * Ficheiros dos animais (fotografia e documentos de vacinas).
 *
 * - Ficam na pasta "animal.path" (params), por defeito fora da pasta pública: só saem pelo PetController,
 *   que confirma que o animal é de quem pede.
 * - Os nomes são sempre gerados aqui (24 hex + extensão): nunca se usa o nome enviado pelo browser no disco.
 * - As imagens são sempre redesenhadas com o GD: apaga metadados (ex.: GPS) e nunca se guarda o original.
 */
final class PetStorage
{
    public const NAME_PATTERN = '/^[a-f0-9]{24}\.(webp|jpg|pdf)$/';

    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    public const PDF_MIME = 'application/pdf';

    /** Máximo de documentos de vacinas por animal. */
    public const MAX_DOCS = 5;

    /** Tamanho máximo pretendido por ficheiro (fica mais baixo se o PHP do servidor não deixar). */
    private const WANTED_MAX_BYTES = 5 * 1024 * 1024;

    public static function dir(): string
    {
        $path = Yii::$app->params['animal.path'] ?? '@frontend/uploads/animais';
        return rtrim(Yii::getAlias($path), '/\\');
    }

    /** Caminho no disco, ou null se o nome não for um dos nossos (impede "../"). */
    public static function path(?string $name): ?string
    {
        return $name !== null && preg_match(self::NAME_PATTERN, $name) ? self::dir() . DIRECTORY_SEPARATOR . $name : null;
    }

    public static function delete(?string $name): void
    {
        $path = self::path($name);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    /** Tamanho máximo por ficheiro: 5 MB, ou menos se upload_max_filesize do PHP for menor. */
    public static function maxBytes(): int
    {
        return min(self::WANTED_MAX_BYTES, self::iniBytes('upload_max_filesize') ?: self::WANTED_MAX_BYTES);
    }

    /** Tamanho máximo de um pedido inteiro (post_max_size). */
    public static function maxPostBytes(): int
    {
        return self::iniBytes('post_max_size') ?: PHP_INT_MAX;
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return str_replace('.', ',', (string) round($bytes / 1024 / 1024, 1)) . ' MB';
        }
        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    /** Tipo real do ficheiro (não confiar na extensão nem no tipo enviado pelo browser). */
    public static function mimeOf(string $tmp): string
    {
        return class_exists('finfo') ? (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) : ((@getimagesize($tmp))['mime'] ?? '');
    }

    /**
     * Guarda uma imagem redesenhada. $square = true recorta ao centro (fotografia do animal).
     * $dir: pasta de destino (por omissão a dos animais; o chat usa a sua, ver ChatStorage).
     *
     * @return string nome do ficheiro gravado
     * @throws \RuntimeException com uma mensagem para o cliente
     */
    public static function saveImage(string $tmp, int $maxSide, bool $square, ?string $dir = null): string
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new \RuntimeException('O servidor não consegue processar imagens (falta a extensão GD do PHP).');
        }
        $info = @getimagesize($tmp);
        if ($info === false || $info[0] < 64 || $info[1] < 64 || $info[0] * $info[1] > 40_000_000) {
            throw new \RuntimeException('Esta imagem não é válida ou é demasiado pequena.');
        }
        $src = @imagecreatefromstring((string) file_get_contents($tmp));
        if ($src === false) {
            throw new \RuntimeException('Não foi possível ler esta imagem.');
        }
        $src = self::fixOrientation($src, $tmp);

        $w = imagesx($src);
        $h = imagesy($src);
        if ($square) {
            $side = min($w, $h);
            $out = min($maxSide, $side);
            $dst = imagecreatetruecolor($out, $out);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), $out, $out, $side, $side);
        } else {
            $scale = min(1, $maxSide / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        }

        $webp = function_exists('imagewebp');
        $name = self::newName($webp ? 'webp' : 'jpg');
        $path = self::writableDir($dir) . DIRECTORY_SEPARATOR . $name;
        $ok = $webp ? @imagewebp($dst, $path, 85) : @imagejpeg($dst, $path, 87);
        if (!$ok) {
            throw new \RuntimeException('Não foi possível guardar a imagem.');
        }
        return $name;
    }

    /**
     * Guarda um PDF tal como veio (depois de confirmar que é mesmo um PDF).
     *
     * @return string nome do ficheiro gravado
     */
    public static function savePdf(string $tmp): string
    {
        $fh = @fopen($tmp, 'rb');
        $head = $fh ? (string) fread($fh, 5) : '';
        if ($fh) {
            fclose($fh);
        }
        if ($head !== '%PDF-') {
            throw new \RuntimeException('Este PDF não é válido.');
        }
        $name = self::newName('pdf');
        if (!@copy($tmp, self::writableDir() . DIRECTORY_SEPARATOR . $name)) {
            throw new \RuntimeException('Não foi possível guardar o PDF.');
        }
        return $name;
    }

    public static function newName(string $ext): string
    {
        return bin2hex(random_bytes(12)) . '.' . $ext;
    }

    public static function writableDir(?string $dir = null): string
    {
        $dir ??= self::dir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            Yii::error('Não foi possível criar a pasta ' . $dir, __METHOD__);
            throw new \RuntimeException('Não foi possível guardar o ficheiro.');
        }
        if (!is_writable($dir)) {
            Yii::error('Sem permissão de escrita em ' . $dir, __METHOD__);
            throw new \RuntimeException('Não foi possível guardar o ficheiro.');
        }
        return $dir;
    }

    /** Fotografias de telemóvel vêm muitas vezes "deitadas" com a rotação só no EXIF. */
    private static function fixOrientation(\GdImage $img, string $tmp): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($tmp);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        return $angle !== 0 ? (imagerotate($img, $angle, 0) ?: $img) : $img;
    }

    public static function iniBytes(string $key): int
    {
        $v = trim((string) ini_get($key));
        if ($v === '' || $v === '0' || $v === '-1') {
            return 0;
        }
        $n = (int) $v;
        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
