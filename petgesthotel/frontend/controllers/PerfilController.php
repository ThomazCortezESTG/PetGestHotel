<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\User;
use common\models\UserProfile;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
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
     * Recebe a fotografia já recortada no browser (perfil.js) e guarda-a.
     * A imagem é sempre redesenhada com o GD: apaga metadados (ex.: GPS) e nunca se guarda o ficheiro original.
     */
    public function actionAvatar(): Response
    {
        $file = UploadedFile::getInstanceByName('avatar');
        $fail = fn(string $msg, int $code = 422): Response => $this->asJson(['ok' => false, 'message' => $msg])
            ->setStatusCode($code);

        if ($file === null || $file->hasError) {
            return $fail('Não recebemos a fotografia. Tente outra vez.');
        }
        if ($file->size > self::AVATAR_MAX_BYTES) {
            return $fail('A fotografia é demasiado grande (máximo 2 MB).');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->tempName);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $fail('Use uma imagem JPG, PNG ou WebP.');
        }
        $info = @getimagesize($file->tempName);
        if ($info === false || $info[0] < 64 || $info[1] < 64 || $info[0] > 4000 || $info[1] > 4000) {
            return $fail('Esta imagem não é válida ou é demasiado pequena.');
        }

        $src = @imagecreatefromstring((string) file_get_contents($file->tempName));
        if ($src === false) {
            return $fail('Não foi possível ler esta imagem.');
        }

        // recorte central quadrado (o browser já envia quadrado; isto só garante) e redimensiona
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $dst = imagecreatetruecolor(self::AVATAR_SIZE, self::AVATAR_SIZE);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), self::AVATAR_SIZE, self::AVATAR_SIZE, $side, $side);

        $dir = Yii::getAlias('@webroot/' . UserProfile::AVATAR_DIR);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $fail('Não foi possível guardar a fotografia.', 500);
        }
        // nome aleatório: não revela o id do cliente e muda sempre (o browser não mostra a antiga da cache)
        $name = bin2hex(random_bytes(12)) . '.webp';
        if (!imagewebp($dst, $dir . DIRECTORY_SEPARATOR . $name, 86)) {
            return $fail('Não foi possível guardar a fotografia.', 500);
        }

        $user = $this->currentUser();
        $profile = $user->userProfile ?? new UserProfile(['user_id' => $user->id]);
        $old = $profile->avatar;
        $profile->avatar = $name;
        $saved = $profile->isNewRecord ? $profile->save(false) : $profile->save(false, ['avatar']);
        if (!$saved) {
            @unlink($dir . DIRECTORY_SEPARATOR . $name);
            return $fail('Não foi possível guardar a fotografia.', 500);
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

    private function deleteAvatarFile(?string $name): void
    {
        // só apaga nomes gerados por nós (evita apagar outro ficheiro se a BD tiver lixo)
        if ($name && preg_match('/^[a-f0-9]{24}\.webp$/', $name)) {
            @unlink(Yii::getAlias('@webroot/' . UserProfile::AVATAR_DIR) . DIRECTORY_SEPARATOR . $name);
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
