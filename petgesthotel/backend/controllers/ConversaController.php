<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Booking;
use common\components\ChatStorage;
use common\models\Conversa;
use common\models\Mensagem;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Conversas com os clientes (back-office).
 *
 * - Colaborador: vê e responde às conversas de que é responsável.
 * - Admin (assignChat): vê todas e passa conversas de um colaborador para outro.
 *   Só escreve nas conversas de que é responsável, como qualquer colaborador.
 */
class ConversaController extends Controller
{
    public const FILTRO_MINHAS = 'minhas';
    public const FILTRO_SEM_RESPONSAVEL = 'sem-responsavel';
    public const FILTRO_TODAS = 'todas';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['reatribuir'], 'roles' => ['assignChat']],
                    ['allow' => true, 'actions' => ['index', 'view', 'mensagens', 'enviar', 'lidas', 'anexo'], 'roles' => ['colaborador']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'enviar' => ['POST'],
                    'lidas' => ['POST'],
                    'reatribuir' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(string $filtro = self::FILTRO_MINHAS)
    {
        $eu = (int) Yii::$app->user->id;
        $admin = Yii::$app->user->can('assignChat');
        if (!$admin) {
            $filtro = self::FILTRO_MINHAS;
        }

        $query = Conversa::find()
            ->alias('c')
            ->innerJoinWith('booking b')
            ->with(['booking.user.userProfile', 'booking.animais', 'colaborador.userProfile', 'ultimaMensagem'])
            // primeiro as reservas ativas, depois a mais recente
            ->orderBy([
                new \yii\db\Expression('b.status IN (:c, :e) DESC', [':c' => Booking::STATUS_CONFIRMADA, ':e' => Booking::STATUS_EM_CURSO]),
                'c.updated_at' => SORT_DESC,
            ]);
        if ($filtro === self::FILTRO_MINHAS) {
            $query->where(['c.id_colaborador' => $eu]);
        } elseif ($filtro === self::FILTRO_SEM_RESPONSAVEL) {
            $query->where(['c.id_colaborador' => null]);
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 30], 'sort' => false]),
            'filtro' => $filtro,
            'admin' => $admin,
            'semResponsavel' => $admin ? (int) Conversa::find()->where(['id_colaborador' => null])->count() : 0,
        ]);
    }

    public function actionView(int $id)
    {
        $conversa = $this->findConversa($id);
        $conversa->marcarLida((int) Yii::$app->user->id);

        return $this->render('view', [
            'conversa' => $conversa,
            'colaboradores' => Yii::$app->user->can('assignChat') ? Conversa::colaboradores() : [],
        ]);
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

        $mensagem = $conversa->enviar(
            $eu,
            (string) Yii::$app->request->post('texto', ''),
            UploadedFile::getInstanceByName('anexo')
        );
        if ($mensagem->hasErrors()) {
            Yii::$app->response->statusCode = 422;
            return ['erro' => $mensagem->getFirstError('texto')];
        }
        return $mensagem->dados($eu, (int) $conversa->booking->id_user);
    }

    /**
     * Fotografia ou vídeo de uma mensagem ($id = id da mensagem), de conversas que o utilizador pode ver.
     */
    public function actionAnexo(int $id): Response
    {
        $mensagem = Mensagem::findOne($id);
        if ($mensagem === null || $mensagem->anexo === null || !$mensagem->conversa->podeVer((int) Yii::$app->user->id)) {
            throw new NotFoundHttpException('Este ficheiro não existe.');
        }
        return ChatStorage::send($mensagem);
    }

    /**
     * Um envio maior do que post_max_size chega ao PHP sem dados (nem o token CSRF) e daria um erro 400
     * sem explicação. Aqui responde em JSON com uma mensagem clara.
     */
    public function beforeAction($action): bool
    {
        $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($action->id === 'enviar' && $_POST === [] && $_FILES === [] && $len > \common\components\PetStorage::maxPostBytes()) {
            $response = Yii::$app->response;
            $response->format = Response::FORMAT_JSON;
            $response->statusCode = 413;
            $response->data = ['erro' => 'O ficheiro é maior do que o servidor aceita (' . \common\components\PetStorage::humanSize(\common\components\PetStorage::maxPostBytes()) . ').'];
            $response->send();
            return false;
        }
        return parent::beforeAction($action);
    }

    public function actionLidas(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->findConversa($id)->marcarLida((int) Yii::$app->user->id);
        return ['ok' => true];
    }

    public function actionReatribuir(int $id)
    {
        $conversa = $this->findConversa($id);
        $novo = (int) Yii::$app->request->post('id_colaborador');

        if ($conversa->reatribuir($novo)) {
            Yii::$app->session->setFlash('success', 'A conversa passou a ser acompanhada por ' . Conversa::pessoa($conversa->colaborador)['nome'] . '. O cliente foi avisado no chat.');
        } else {
            Yii::$app->session->setFlash('error', $conversa->getFirstError('id_colaborador') ?: 'Não foi possível reatribuir a conversa.');
        }
        return $this->redirect(['view', 'id' => $conversa->id]);
    }

    private function findConversa(int $id): Conversa
    {
        $conversa = Conversa::findOne($id);
        if ($conversa === null || !$conversa->podeVer((int) Yii::$app->user->id)) {
            throw new NotFoundHttpException('Esta conversa não existe ou não está atribuída a si.');
        }
        return $conversa;
    }

    /**
     * Total de mensagens por ler nas conversas de que o utilizador é responsável (para o menu lateral).
     */
    public static function naoLidasDe(int $idUser): int
    {
        $total = 0;
        foreach (Conversa::find()->with('booking')->where(['id_colaborador' => $idUser])->all() as $c) {
            $total += $c->naoLidas($idUser);
        }
        return $total;
    }
}
