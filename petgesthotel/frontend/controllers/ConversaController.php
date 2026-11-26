<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\components\ChatStorage;
use common\models\Conversa;
use common\models\Mensagem;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Mensagens do cliente com o colaborador responsável por cada reserva.
 *
 * Páginas: index (lista), view (conversa).
 * Pedidos do chat.js (JSON): mensagens, enviar, lidas.
 */
class ConversaController extends Controller
{
    public $layout = 'landing';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['cliente']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'enviar' => ['POST'],
                    'lidas' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $conversas = Conversa::find()
            ->alias('c')
            ->innerJoinWith('booking b')
            ->with(['colaborador.userProfile', 'booking.animais', 'ultimaMensagem'])
            ->where(['b.id_user' => Yii::$app->user->id])
            ->orderBy(['c.updated_at' => SORT_DESC])
            ->all();

        // Com uma só conversa, vai-se direto a ela
        if (count($conversas) === 1) {
            return $this->redirect(['view', 'id' => $conversas[0]->id]);
        }

        return $this->render('index', ['conversas' => $conversas]);
    }

    public function actionView(int $id)
    {
        $conversa = $this->findConversa($id);
        $conversa->marcarLida((int) Yii::$app->user->id);

        $outras = (int) Conversa::find()
            ->alias('c')
            ->innerJoinWith('booking b', false)
            ->where(['b.id_user' => Yii::$app->user->id])
            ->count();

        return $this->render('view', ['conversa' => $conversa, 'temOutras' => $outras > 1]);
    }

    public function actionMensagens(int $id, int $depois = 0): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->findConversa($id)->mensagensDados((int) Yii::$app->user->id, $depois);
    }

    public function actionEnviar(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $eu = (int) Yii::$app->user->id;
        $conversa = $this->findConversa($id);

        $mensagem = $conversa->enviar($eu, (string) Yii::$app->request->post('texto', ''));
        if ($mensagem->hasErrors()) {
            Yii::$app->response->statusCode = 422;
            return ['erro' => $mensagem->getFirstError('texto')];
        }
        return $mensagem->dados($eu, $eu);
    }

    public function actionLidas(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->findConversa($id)->marcarLida((int) Yii::$app->user->id);
        return ['ok' => true];
    }

    /**
     * Fotografia ou vídeo de uma mensagem ($id = id da mensagem), só de conversas do próprio cliente.
     */
    public function actionAnexo(int $id): Response
    {
        $mensagem = Mensagem::find()
            ->alias('m')
            ->innerJoinWith('conversa.booking b', false)
            ->where(['m.id' => $id, 'b.id_user' => Yii::$app->user->id])
            ->andWhere(['not', ['m.anexo' => null]])
            ->one();
        if ($mensagem === null) {
            throw new NotFoundHttpException('Este ficheiro não existe.');
        }
        return ChatStorage::send($mensagem);
    }

    /**
     * Só as conversas das reservas do próprio cliente (as outras dão 404).
     */
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
