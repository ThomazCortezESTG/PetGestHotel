<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\User;
use common\models\UserProfile;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Perfil do cliente (RF-FO-10 criar perfil, RF-FO-11 editar perfil).
 * Cada utilizador só vê e edita o seu próprio perfil: nunca recebe um id pelo URL.
 */
class PerfilController extends Controller
{
    public $layout = 'landing';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['foto'], 'roles' => ['?', '@']],
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'avatar' => ['POST'],
                    'avatar-remove' => ['POST'],
                ],
            ],
        ];
    }

    /** Tamanho final da fotografia (quadrada) e limite do pedido. */
    private const AVATAR_SIZE = 512;
    private const AVATAR_MAX_BYTES = 2 * 1024 * 1024;

    /**
     * Recebe a fotografia já recortada no browser (avatar.js) e guarda-a.
     * A imagem é sempre redesenhada com o GD: apaga metadados (ex.: GPS) e nunca se guarda o ficheiro original.
     * Responde sempre em JSON, mesmo quando algo falha no servidor (o motivo fica no log: runtime/logs/app.log).
     */
    public function actionAvatar(): Response
    {
        try {
            return $this->saveAvatar();
        } catch (\Throwable $e) {
            Yii::error('Falha ao guardar fotografia de perfil: ' . $e, __METHOD__);
            return $this->avatarFail('Não foi possível guardar a fotografia.', 500, get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * Resposta de erro em JSON. O detalhe técnico só é mostrado em modo debug; fica sempre no log.
     */
    private function avatarFail(string $msg, int $code = 422, ?string $detail = null): Response
    {
        if ($detail !== null) {
            Yii::warning('Fotografia de perfil: ' . $detail, __METHOD__);
            if (YII_DEBUG) {
                $msg .= ' (' . $detail . ')';
            }
        }
        return $this->asJson(['ok' => false, 'message' => $msg])->setStatusCode($code);
    }

    private function saveAvatar(): Response
    {
        if (!function_exists('imagecreatefromstring')) {
            return $this->avatarFail('Não foi possível guardar a fotografia.', 500, 'a extensão GD do PHP não está instalada');
        }

        $file = UploadedFile::getInstanceByName('avatar');
        if ($file === null || $file->hasError) {
            return $this->avatarFail('Não recebemos a fotografia. Tente outra vez.', 422, 'upload vazio ou com erro ' . ($file->error ?? 'sem ficheiro'));
        }
        if ($file->size > self::AVATAR_MAX_BYTES) {
            return $this->avatarFail('A fotografia é demasiado grande (máximo 2 MB).');
        }

        // tipo real do ficheiro (não confiar na extensão nem no tipo enviado pelo browser)
        $info = @getimagesize($file->tempName);
        $mime = class_exists('finfo')
            ? (new \finfo(FILEINFO_MIME_TYPE))->file($file->tempName)
            : ($info['mime'] ?? '');
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $this->avatarFail('Use uma imagem JPG, PNG ou WebP.');
        }
        if ($info === false || $info[0] < 64 || $info[1] < 64 || $info[0] > 4000 || $info[1] > 4000) {
            return $this->avatarFail('Esta imagem não é válida ou é demasiado pequena.');
        }

        $src = @imagecreatefromstring((string) file_get_contents($file->tempName));
        if ($src === false) {
            return $this->avatarFail('Não foi possível ler esta imagem.', 422, 'o GD não conseguiu abrir um ' . $mime);
        }

        // recorte central quadrado (o browser já envia quadrado; isto só garante) e redimensiona
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $dst = imagecreatetruecolor(self::AVATAR_SIZE, self::AVATAR_SIZE);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), self::AVATAR_SIZE, self::AVATAR_SIZE, $side, $side);

        $dir = UserProfile::avatarDir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->avatarFail('Não foi possível guardar a fotografia.', 500, 'não foi possível criar a pasta ' . $dir . ' (avatar.path)');
        }
        if (!is_writable($dir)) {
            return $this->avatarFail('Não foi possível guardar a fotografia.', 500, 'sem permissão de escrita em ' . $dir . ' (avatar.path)');
        }

        // WebP se o GD do servidor o suportar; senão JPG. Nome aleatório: não revela o id e muda sempre (sem cache antiga).
        $webp = function_exists('imagewebp');
        $name = bin2hex(random_bytes(12)) . ($webp ? '.webp' : '.jpg');
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        $written = $webp ? @imagewebp($dst, $path, 86) : @imagejpeg($dst, $path, 88);
        if (!$written) {
            return $this->avatarFail('Não foi possível guardar a fotografia.', 500, 'falhou a escrita de ' . $name);
        }

        $user = $this->currentUser();
        $profile = $user->userProfile ?? new UserProfile(['user_id' => $user->id]);
        if (!$profile->hasAttribute('avatar')) {
            @unlink($path);
            return $this->avatarFail('Não foi possível guardar a fotografia.', 500, 'falta a coluna user_profile.avatar (correr php yii migrate)');
        }
        $old = $profile->avatar;
        $profile->avatar = $name;
        try {
            $saved = $profile->isNewRecord ? $profile->save(false) : $profile->save(false, ['avatar']);
        } catch (\Throwable $e) {
            @unlink($path); // não deixar ficheiros órfãos se a base de dados falhar
            throw $e;
        }
        if (!$saved) {
            @unlink($path);
            return $this->avatarFail('Não foi possível guardar a fotografia.', 500, 'falhou a gravação na base de dados');
        }
        $this->deleteAvatarFile($old);

        return $this->asJson(['ok' => true, 'url' => $profile->getAvatarUrl(), 'message' => 'Fotografia atualizada.']);
    }

    /**
     * Remove a fotografia (volta às iniciais).
     */
    public function actionAvatarRemove(): Response
    {
        $profile = $this->currentUser()->userProfile;
        if ($profile !== null && $profile->avatar) {
            $old = $profile->avatar;
            $profile->avatar = null;
            $profile->save(false, ['avatar']);
            $this->deleteAvatarFile($old);
        }

        return $this->asJson([
            'ok' => true,
            'initials' => UserProfile::initialsOf((string) ($profile->nome ?? $this->currentUser()->username)),
            'message' => 'Fotografia removida.',
        ]);
    }

    /**
     * Entrega uma fotografia de perfil a partir da pasta configurada (avatar.path).
     * Pública (aparece também no backend). Os nomes são aleatórios e só aceitamos os que nós geramos.
     */
    public function actionFoto(string $f): Response
    {
        $path = UserProfile::avatarFile($f);
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Fotografia não encontrada.');
        }

        $response = Yii::$app->response;
        // o nome muda a cada upload, por isso pode ficar em cache "para sempre"
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response->sendFile($path, $f, [
            'mimeType' => str_ends_with($f, '.webp') ? 'image/webp' : 'image/jpeg',
            'inline' => true,
        ]);
    }

    private function deleteAvatarFile(?string $name): void
    {
        // só apaga nomes gerados por nós (evita apagar outro ficheiro se a BD tiver lixo)
        $path = $name ? UserProfile::avatarFile($name) : null;
        if ($path !== null) {
            @unlink($path);
        }
    }

    /**
     * Mostra o perfil. Se ainda não existir, manda o cliente preenchê-lo.
     */
    public function actionIndex(): string|Response
    {
        $user = $this->currentUser();
        $profile = $user->userProfile;

        if ($profile === null || !$profile->isComplete()) {
            return $this->redirect(['update']);
        }

        return $this->render('index', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Cria (primeira vez) ou edita o perfil.
     */
    public function actionUpdate(): string|Response
    {
        $user = $this->currentUser();
        $profile = $user->userProfile ?? new UserProfile(['user_id' => $user->id]);
        $isNew = $profile->isNewRecord || !$profile->isComplete();
        $profile->scenario = UserProfile::SCENARIO_CLIENTE;

        if ($profile->load(Yii::$app->request->post())) {
            $profile->user_id = $user->id; // nunca confiar num user_id vindo do formulário

            if ($profile->save()) {
                Yii::$app->session->setFlash('success', $isNew
                    ? 'Perfil criado. Já pode adicionar o seu primeiro animal.'
                    : 'Perfil atualizado.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'user' => $user,
            'profile' => $profile,
            'isNew' => $isNew,
        ]);
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        return $user;
    }
}
