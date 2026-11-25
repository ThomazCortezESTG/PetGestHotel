<?php

declare(strict_types=1);

namespace backend\modules\api\controllers;

use common\components\ChatStorage;
use common\models\Conversa;
use common\models\Mensagem;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Chat do cliente com o colaborador responsável por cada reserva (só clientes: a app Android
 * não tem área de colaborador).
 *
 * GET  api/conversas                                -> as minhas conversas (uma por reserva confirmada)
 * GET  api/conversas/{id}                           -> master-detail: a conversa e as mensagens
 * GET  api/conversas/{id}/mensagens?depois={idMsg}  -> só as mensagens novas (depois de idMsg)
 * POST api/conversas/{id}/mensagens  {"texto": "..."}
 * POST api/conversas/{id}/lida                      -> marca tudo como lido
 * GET  api/conversas/{id}/mensagens/{m}/anexo       -> fotografia ou vídeo da mensagem {m}
 *
 * As mensagens com fotografia/vídeo trazem "anexo": {"tipo": "imagem"|"video", "mime", "tamanho", "url"}.
 * Só o colaborador (no back-office) envia anexos; pela API, o cliente envia só texto.
 *
 * Tempo real: cada conversa traz "mqtt_topic" (ex.: "petgest/conversa/3"). A app subscreve-o
 * no Mosquitto (porta 1883) e, quando chega um aviso, pede GET .../mensagens?depois=<última que tem>.
 * O aviso só traz ids; o texto vem sempre por aqui.
 */
class ConversaController extends Controller
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
            'view' => ['GET', 'HEAD'],
            'mensagens' => ['GET', 'HEAD'],
            'enviar' => ['POST'],
            'lida' => ['POST'],
            'anexo' => ['GET', 'HEAD'],
        ];
    }

    public function actionIndex(): array
    {
        $eu = (int) Yii::$app->user->id;
        $conversas = Conversa::find()
            ->alias('c')
            ->innerJoinWith('booking b')
            ->with(['colaborador.userProfile', 'booking.animais', 'booking.user.userProfile', 'ultimaMensagem'])
            ->where(['b.id_user' => $eu])
            ->orderBy(['c.updated_at' => SORT_DESC])
            ->all();

        return array_map(static fn (Conversa $c) => $c->dados($eu), $conversas);
    }

    public function actionView(int $id): array
    {
        $eu = (int) Yii::$app->user->id;
        $conversa = $this->findConversa($id);

        return $conversa->dados($eu) + ['mensagens' => $conversa->mensagensDados($eu)];
    }

    public function actionMensagens(int $id, int $depois = 0): array
    {
        return $this->findConversa($id)->mensagensDados((int) Yii::$app->user->id, $depois);
    }

    public function actionEnviar(int $id): array
    {
        $eu = (int) Yii::$app->user->id;
        $conversa = $this->findConversa($id);

        $mensagem = $conversa->enviar($eu, (string) Yii::$app->request->getBodyParam('texto', ''));
        if ($mensagem->hasErrors()) {
            Yii::$app->response->statusCode = 422;
            return [['field' => 'texto', 'message' => $mensagem->getFirstError('texto')]];
        }

        Yii::$app->response->statusCode = 201;
        return $mensagem->dados($eu, $eu);
    }

    public function actionLida(int $id): array
    {
        $this->findConversa($id)->marcarLida((int) Yii::$app->user->id);
        return ['ok' => true];
    }

    /**
     * Fotografia ou vídeo de uma mensagem (o "url" do anexo nas mensagens aponta para aqui).
     * Pedir com o mesmo cabeçalho Authorization; os vídeos aceitam Range (para o player saltar).
     */
    public function actionAnexo(int $id, int $mensagem): Response
    {
        $conversa = $this->findConversa($id);
        $m = Mensagem::findOne(['id' => $mensagem, 'id_conversa' => $conversa->id]);
        if ($m === null || $m->anexo === null) {
            throw new NotFoundHttpException('Este ficheiro não existe.');
        }
        return ChatStorage::send($m);
    }

    private function findConversa(int $id): Conversa
    {
        $conversa = Conversa::find()
            ->alias('c')
            ->innerJoinWith('booking b')
            ->where(['c.id' => $id, 'b.id_user' => Yii::$app->user->id])
            ->one();
        if ($conversa === null) {
            throw new NotFoundHttpException('Esta conversa não existe ou não é de uma reserva sua.');
        }
        return $conversa;
    }
}
