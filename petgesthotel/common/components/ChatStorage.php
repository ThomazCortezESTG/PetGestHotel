<?php

declare(strict_types=1);

namespace common\components;

use Yii;
use yii\web\UploadedFile;

/**
 * Fotografias e vídeos enviados no chat (pelo colaborador ao cliente).
 *
 * - Ficam na pasta "chat.path" (params), fora da pasta pública: só saem pelos controladores das conversas,
 *   que confirmam que quem pede pode ver a conversa.
 * - Fotografias: redesenhadas com o GD (apaga metadados, como o GPS) e reduzidas a 1600 px.
 * - Vídeos: MP4, WebM ou MOV, guardados como vieram (o PHP não converte vídeo), depois de confirmar o formato.
 * - O tamanho máximo é o mais baixo entre o desejado aqui e o upload_max_filesize do PHP.
 */
final class ChatStorage
{
    public const NAME_PATTERN = '/^[a-f0-9]{24}\.(webp|jpg|mp4|webm|mov)$/';

    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    public const VIDEO_MIMES = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];

    private const WANTED_IMAGE_BYTES = 10 * 1024 * 1024;
    private const WANTED_VIDEO_BYTES = 50 * 1024 * 1024;

    public static function dir(): string
    {
        $path = Yii::$app->params['chat.path'] ?? '@frontend/uploads/chat';
        return rtrim(Yii::getAlias($path), '/\\');
    }

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

    public static function maxImageBytes(): int
    {
        return self::limit(self::WANTED_IMAGE_BYTES);
    }

    public static function maxVideoBytes(): int
    {
        return self::limit(self::WANTED_VIDEO_BYTES);
    }

    public static function isVideoMime(?string $mime): bool
    {
        return $mime !== null && str_starts_with($mime, 'video/');
    }

    /**
     * Guarda o ficheiro enviado.
     *
     * @return array{0: string, 1: string, 2: int} [nome do ficheiro, mime, bytes]
     * @throws \RuntimeException com uma mensagem para quem enviou
     */
    public static function save(UploadedFile $file): array
    {
        if ($file->error === UPLOAD_ERR_INI_SIZE || $file->error === UPLOAD_ERR_FORM_SIZE) {
            throw new \RuntimeException('O ficheiro é maior do que o servidor aceita (' . PetStorage::humanSize(self::limit(PHP_INT_MAX)) . ').');
        }
        if ($file->error !== UPLOAD_ERR_OK || !is_uploaded_file($file->tempName)) {
            throw new \RuntimeException('O ficheiro não chegou ao servidor. Tente outra vez.');
        }

        $mime = PetStorage::mimeOf($file->tempName);

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            if ($file->size > self::maxImageBytes()) {
                throw new \RuntimeException('A fotografia tem mais de ' . PetStorage::humanSize(self::maxImageBytes()) . '.');
            }
            $name = PetStorage::saveImage($file->tempName, 1600, false, self::dir());
            $saved = self::dir() . DIRECTORY_SEPARATOR . $name;
            return [$name, str_ends_with($name, '.webp') ? 'image/webp' : 'image/jpeg', (int) filesize($saved)];
        }

        if (isset(self::VIDEO_MIMES[$mime])) {
            if ($file->size > self::maxVideoBytes()) {
                throw new \RuntimeException('O vídeo tem mais de ' . PetStorage::humanSize(self::maxVideoBytes()) . '.');
            }
            if (!self::looksLikeVideo($file->tempName, $mime)) {
                throw new \RuntimeException('Este vídeo não é válido.');
            }
            $name = PetStorage::newName(self::VIDEO_MIMES[$mime]);
            if (!@move_uploaded_file($file->tempName, PetStorage::writableDir(self::dir()) . DIRECTORY_SEPARATOR . $name)) {
                throw new \RuntimeException('Não foi possível guardar o vídeo.');
            }
            return [$name, $mime, (int) $file->size];
        }

        throw new \RuntimeException('Só pode enviar fotografias (JPG, PNG, WebP) ou vídeos (MP4, WebM, MOV).');
    }

    /**
     * Envia o anexo de uma mensagem ao browser/app (quem chama já confirmou que pode vê-lo).
     * Os vídeos aceitam pedidos parciais (Range), para se poder saltar para meio sem descarregar tudo.
     */
    public static function send(\common\models\Mensagem $mensagem): \yii\web\Response
    {
        $path = self::path($mensagem->anexo);
        if ($path === null || !is_file($path)) {
            throw new \yii\web\NotFoundHttpException('Este ficheiro já não existe.');
        }
        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', 'private, max-age=86400');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox");

        return $response->sendFile($path, $mensagem->anexo, ['mimeType' => (string) $mensagem->anexo_mime, 'inline' => true]);
    }

    /** Confirma a "assinatura" do ficheiro (não basta o tipo que o browser diz). */
    private static function looksLikeVideo(string $tmp, string $mime): bool
    {
        $fh = @fopen($tmp, 'rb');
        $head = $fh ? (string) fread($fh, 12) : '';
        if ($fh) {
            fclose($fh);
        }
        if ($mime === 'video/webm') {
            return str_starts_with($head, "\x1A\x45\xDF\xA3");
        }
        // MP4 e MOV: "ftyp" a partir do 5.º byte
        return substr($head, 4, 4) === 'ftyp';
    }

    private static function limit(int $wanted): int
    {
        return min($wanted, PetStorage::iniBytes('upload_max_filesize') ?: $wanted, PetStorage::maxPostBytes());
    }
}
