<?php

namespace backend\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use common\models\LoginForm;

class AuthController extends Controller
{
    public function actionLogin()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
            $user = Yii::$app->user->identity;

            return [
                'status' => 'success',
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'token' => $user->auth_key,
            ];
        }

        Yii::$app->response->statusCode = 401;
        return [
            'status' => 'error',
            'message' => 'Credenciais inválidas.',
            'errors' => $model->errors,
        ];
    }
}
