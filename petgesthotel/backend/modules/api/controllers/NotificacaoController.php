<?php

declare(strict_types=1);

namespace backend\modules\api\controllers;

use common\models\Notificacao;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

/**
 * Notificações do cliente (as mesmas do sino do site).
 *
 * GET  api/notificacoes              -> {"nao_lidas": 2, "itens": [...]} (as 30 mais recentes)
 * POST api/notificacoes/{id}/lida    -> marca uma como lida
 * POST api/notificacoes/lidas        -> marca todas como lidas
 *
 * Cada item: id, tipo ("mensagem"), ref_id (no tipo "mensagem" é o id da conversa), titulo, texto,
 * quantidade, lida, created_at, updated_at.
 *
 * Tempo real: subscrever o tópico "mqtt_topic" de GET api/auth/me (ex.: "petgest/utilizador/12").
 * Cada aviso significa "a lista mudou": pedir GET api/notificacoes outra vez.
 */
class NotificacaoController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = ['class' => HttpBearerAuth::class];
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                ['allow' => true, 'roles' => ['viewOwnBooking']],
            ],
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'index' => ['GET', 'HEAD'],
            'lida' => ['POST'],
            'lidas' => ['POST'],
        ];
    }

    public function actionIndex(): array
    {
        $eu = (int) Yii::$app->user->id;
        return [
            'nao_lidas' => Notificacao::naoLidas($eu),
            'itens' => array_map(static fn (Notificacao $n) => $n->dados(), Notificacao::recentes($eu, 30)),
        ];
    }

    public function actionLida(int $id): array
    {
        $n = Notificacao::findOne(['id' => $id, 'id_user' => Yii::$app->user->id]);
        if ($n === null) {
            throw new NotFoundHttpException('Esta notificação não existe.');
        }
        $n->lida = true;
        $n->save(false, ['lida']);
        return $n->dados();
    }

    public function actionLidas(): array
    {
        return ['marcadas' => Notificacao::marcarLidas((int) Yii::$app->user->id)];
    }
}
