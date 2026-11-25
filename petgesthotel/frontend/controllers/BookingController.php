<?php

namespace frontend\controllers;

use Yii;
use common\models\Booking;
use common\models\Quarto;
use common\models\Animal;
use common\models\ServicosExtras;
use common\models\BookingAnimalQuarto;
use common\models\BookingAnimalServico;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Reservas do cliente: pedir, ver, alterar (só enquanto está pendente) e cancelar.
 * Aceitar, rejeitar, check-in e check-out são no back-office (backend\controllers\BookingController).
 *
 * Só se veem e alteram as reservas do próprio cliente (findModel filtra por id_user).
 */
class BookingController extends Controller
{
    public $layout = 'landing';

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        ['allow' => true, 'actions' => ['create'], 'roles' => ['createBooking']],
                        ['allow' => true, 'actions' => ['update'], 'roles' => ['editOwnBooking']],
                        ['allow' => true, 'actions' => ['cancelar'], 'roles' => ['cancelOwnBooking']],
                        ['allow' => true, 'actions' => ['index', 'view'], 'roles' => ['viewOwnBooking']],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'cancelar' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * As reservas do cliente: primeiro as que ainda vão acontecer ou estão a decorrer, depois as anteriores.
     *
     * @return string
     */
    public function actionIndex()
    {
        $reservas = Booking::find()
            ->with(['bookingAnimalQuartos.animal.racaPrincipal', 'bookingAnimalQuartos.quarto', 'conversa.colaborador.userProfile'])
            ->where(['id_user' => Yii::$app->user->id])
            ->orderBy(['datainicio' => SORT_DESC])
            ->all();

        $ativas = [Booking::STATUS_PENDENTE, Booking::STATUS_CONFIRMADA, Booking::STATUS_EM_CURSO];
        $proximas = array_values(array_filter($reservas, fn (Booking $b) => in_array($b->status, $ativas, true)));
        // as próximas pela ordem em que acontecem
        usort($proximas, fn (Booking $a, Booking $b) => strcmp($a->datainicio, $b->datainicio));

        return $this->render('index', [
            'proximas' => $proximas,
            'anteriores' => array_values(array_filter($reservas, fn (Booking $b) => !in_array($b->status, $ativas, true))),
            'temAnimais' => Animal::find()->where(['id_user' => Yii::$app->user->id])->exists(),
        ]);
    }

    /**
     * Displays a single Booking model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Pedir uma reserva (fica pendente até o hotel a aceitar no back-office).
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Booking();
        $escolhasQuartos = [];
        $escolhasServicos = [];

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->id_user = Yii::$app->user->id;
            $model->status  = Booking::STATUS_PENDENTE;
            $model->preco   = 0; // recalculado depois

            $escolhasQuartos  = array_filter((array) $this->request->post('quartos', []));      // [id_animal => id_quarto]
            $escolhasServicos = array_unique(array_filter((array) $this->request->post('servicos', [])));

            if ($this->guardar($model, $escolhasQuartos, $escolhasServicos)) {
                Yii::$app->session->setFlash('success', 'Pedido de reserva enviado. Vai receber uma notificação quando o hotel responder.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', $this->dadosFormulario($model, $escolhasQuartos, $escolhasServicos));
    }

    /**
     * Alterar o pedido: só enquanto o hotel ainda não respondeu (pendente).
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if (!$model->podeSerEditadaPeloCliente()) {
            Yii::$app->session->setFlash('warning', 'Esta reserva já foi ' . mb_strtolower($model->getStatusLabel()) . ' pelo hotel, por isso já não pode ser alterada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // escolhas já guardadas
        $linhas = $model->bookingAnimalQuartos;
        $escolhasQuartos = [];   // [id_animal => id_quarto]
        foreach ($linhas as $l) {
            $escolhasQuartos[$l->id_animal] = $l->id_quarto;
        }
        $escolhasServicos = BookingAnimalServico::find()
            ->select('id_servico')
            ->where(['id_booking_animal' => array_map(fn ($l) => $l->id, $linhas)])
            ->distinct()
            ->column();

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->status = Booking::STATUS_PENDENTE; // o formulário não pode mudar o estado
            $escolhasQuartos  = array_filter((array) $this->request->post('quartos', []));
            $escolhasServicos = array_unique(array_filter((array) $this->request->post('servicos', [])));

            if ($this->guardar($model, $escolhasQuartos, $escolhasServicos)) {
                Yii::$app->session->setFlash('success', 'Pedido de reserva atualizado.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', $this->dadosFormulario($model, $escolhasQuartos, $escolhasServicos));
    }

    /**
     * Cancelar (em vez de apagar: o hotel e o chat precisam do histórico).
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionCancelar($id)
    {
        $model = $this->findModel($id);
        if ($model->cancelar()) {
            Yii::$app->session->setFlash('success', 'Reserva cancelada.');
        } else {
            Yii::$app->session->setFlash('error', $model->getFirstError('status'));
        }
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Guarda a reserva e as linhas (um quarto por animal; os serviços aplicam-se a cada animal).
     * Em caso de erro, deixa uma mensagem flash e devolve false (nada fica gravado).
     */
    private function guardar(Booking $model, array $escolhasQuartos, array $escolhasServicos): bool
    {
        $nova = $model->isNewRecord;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (empty($escolhasQuartos)) {
                throw new \DomainException('Escolha pelo menos um animal e o quarto onde vai ficar.');
            }
            if ($nova && $model->datainicio < date('Y-m-d')) {
                $model->addError('datainicio', 'A data de entrada não pode ser no passado.');
            }
            if ($model->hasErrors() || !$model->save()) {
                throw new \DomainException('Confirme as datas da estadia.');
            }

            // apaga as ligações antigas e recria (serviços primeiro, por causa da FK)
            $idsAntigos = BookingAnimalQuarto::find()->select('id')->where(['id_booking' => $model->id])->column();
            if ($idsAntigos) {
                BookingAnimalServico::deleteAll(['id_booking_animal' => $idsAntigos]);
            }
            BookingAnimalQuarto::deleteAll(['id_booking' => $model->id]);

            $servicosValidos = ServicosExtras::find()->select('id')->where(['id' => $escolhasServicos])->column();
            foreach ($escolhasQuartos as $idAnimal => $idQuarto) {
                $animal = Animal::findOne(['id' => $idAnimal, 'id_user' => Yii::$app->user->id]);
                $quarto = Quarto::findOne($idQuarto);
                if (!$animal || !$quarto) {
                    throw new \DomainException('Animal ou quarto inválido.');
                }

                $baq = new BookingAnimalQuarto([
                    'id_booking' => $model->id,
                    'id_animal'  => $animal->id,
                    'id_quarto'  => $quarto->id,
                ]);
                if (!$baq->save(false)) {
                    throw new \DomainException('Não foi possível guardar o quarto.');
                }

                foreach ($servicosValidos as $idServico) {
                    (new BookingAnimalServico([
                        'id_booking_animal' => $baq->id,
                        'id_servico'        => $idServico,
                    ]))->save(false);
                }
            }

            $model->calcularPreco();
            $transaction->commit();
            return true;
        } catch (\DomainException $e) {
            $transaction->rollBack();
            if ($nova) {
                // se a reserva chegou a ser inserida antes do erro, com o rollback volta a ser nova
                $model->setAttribute('id', null);
                $model->setIsNewRecord(true);
            }
            Yii::$app->session->setFlash('error', $e->getMessage());
            return false;
        }
    }

    private function dadosFormulario(Booking $model, array $escolhasQuartos, array $escolhasServicos): array
    {
        return [
            'model'            => $model,
            'quartos'          => Quarto::find()->orderBy(['preco' => SORT_ASC])->all(),
            'animais'          => Animal::find()->with('racaPrincipal')->where(['id_user' => Yii::$app->user->id])->orderBy(['nome' => SORT_ASC])->all(),
            'servicos'         => ServicosExtras::find()->orderBy(['preco' => SORT_ASC])->all(),
            'escolhasQuartos'  => $escolhasQuartos,
            'escolhasServicos' => $escolhasServicos,
        ];
    }

    /**
     * Só as reservas do próprio cliente: o número de uma reserva alheia dá 404.
     * @param int $id ID
     * @return Booking the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = Booking::find()
            ->with(['bookingAnimalQuartos.animal.racaPrincipal', 'bookingAnimalQuartos.quarto', 'bookingAnimalQuartos.servicos', 'conversa.colaborador.userProfile'])
            ->where(['id' => $id, 'id_user' => Yii::$app->user->id])
            ->one();
        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Esta reserva não existe ou não é sua.');
    }
}
