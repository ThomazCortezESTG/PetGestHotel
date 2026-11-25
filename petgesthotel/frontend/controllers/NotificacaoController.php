<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Notificacao;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Sino de notificações do cliente (no topo do site). O JS está em web/js/notificacoes.js.
 *
 * lista  (GET, JSON)  -> contador e as mais recentes
 * abrir  (GET)        -> marca como lida e leva à página certa (ex.: a conversa)
 * lidas  (POST, JSON) -> marca todas como lidas
 */
class NotificacaoController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['viewOwnBooking']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['lidas' => ['POST']],
            ],
        ];
    }

    public function actionLista(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $eu = (int) Yii::$app->user->id;

        return [
            'nao_lidas' => Notificacao::naoLidas($eu),
            'itens' => array_map(
                fn (Notificacao $n) => $n->dados() + ['url' => \yii\helpers\Url::to(['abrir', 'id' => $n->id])],
                Notificacao::recentes($eu)
            ),
        ];
    }

    public function actionAbrir(int $id): Response
    {
        $n = Notificacao::findOne(['id' => $id, 'id_user' => Yii::$app->user->id]);
        if ($n === null) {
            throw new NotFoundHttpException('Esta notificação não existe.');
        }
        if (!$n->lida) {
            $n->lida = true;
            $n->save(false, ['lida']);
        }
        return $this->redirect($n->url() ?? Yii::$app->homeUrl);
    }

    public function actionLidas(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Notificacao::marcarLidas((int) Yii::$app->user->id);
        return ['ok' => true];
    }
}
