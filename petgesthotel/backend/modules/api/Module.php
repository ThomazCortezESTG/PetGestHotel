<?php

declare(strict_types=1);

namespace backend\modules\api;

use Yii;
use yii\web\Response;

/**
 * API REST do PetGestHotel, usada pela aplicação móvel (Android).
 *
 * - Endereço base: <backend>/api/... (ex.: http://localhost/PetGestHotel/petgesthotel/backend/web/api/animais)
 * - Responde sempre em JSON, incluindo os erros.
 * - Autenticação: POST api/auth/login devolve um token; os outros pedidos enviam
 *   o cabeçalho "Authorization: Bearer <token>". Não usa sessão nem cookies.
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\api\controllers';

    public function init(): void
    {
        parent::init();

        // Sem sessão: cada pedido identifica-se só pelo token
        Yii::$app->user->enableSession = false;
        Yii::$app->user->loginUrl = null;

        // Erros em JSON (sem isto, o backend mostrava a página HTML site/error)
        Yii::$app->errorHandler->errorAction = null;
        Yii::$app->response->format = Response::FORMAT_JSON;
    }
}
