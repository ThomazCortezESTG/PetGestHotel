<?php

declare(strict_types=1);

namespace backend\modules\api\controllers;

use common\models\User;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Autenticação da app móvel.
 *
 * POST api/auth/login   {"username": "...", "password": "..."}  -> {"token": "...", "user": {...}}
 * POST api/auth/logout  (com token)                              -> invalida o token
 * GET  api/auth/me      (com token)                              -> dados do utilizador autenticado
 */
class AuthController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login'],
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'login' => ['POST'],
            'logout' => ['POST'],
            'me' => ['GET'],
        ];
    }

    /**
     * Aceita o nome de utilizador ou o e-mail no campo "username".
     */
    public function actionLogin(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $login = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        if ($login === '' || $password === '') {
            throw new BadRequestHttpException('Indique o nome de utilizador (ou e-mail) e a palavra-passe.');
        }

        $user = User::findByUsernameOrEmail($login);
        if ($user === null || !$user->validatePassword($password)) {
            throw new UnauthorizedHttpException('Nome de utilizador ou palavra-passe incorretos.');
        }

        // Reutiliza o token se já existir, para não terminar a sessão noutro telemóvel
        if (empty($user->access_token)) {
            $user->generateAccessToken();
            $user->save(false, ['access_token']);
        }

        return [
            'token' => $user->access_token,
            'user' => $this->userData($user),
        ];
    }

    /**
     * Invalida o token: a app tem de fazer login outra vez (em todos os dispositivos).
     */
    public function actionLogout(): array
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $user->access_token = null;
        $user->save(false, ['access_token']);

        return ['ok' => true];
    }

    public function actionMe(): array
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        return $this->userData($user);
    }

    private function userData(User $user): array
    {
        $profile = $user->userProfile;
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'nome' => $profile?->nome,
            'telefone' => $profile?->telefone,
            'morada' => $profile?->morada,
            // tópico MQTT das notificações deste utilizador (ver api/notificacoes)
            'mqtt_topic' => \common\models\Notificacao::topico($user->id),
        ];
    }
}
