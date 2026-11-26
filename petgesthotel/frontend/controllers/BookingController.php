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
                        ['allow' => true, 'roles' => ['cliente']],
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
        $escolhas = [];

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->id_user = Yii::$app->user->id;
            $model->status  = Booking::STATUS_PENDENTE;
            $model->preco   = 0; // recalculado depois

            $escolhas = $this->escolhasDoPedido();
            if ($this->guardar($model, $escolhas)) {
                Yii::$app->session->setFlash('success', 'Pedido de reserva enviado. Vai receber uma notificação quando o hotel responder.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', $this->dadosFormulario($model, $escolhas));
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

        // escolhas já guardadas, no mesmo formato que o formulário envia
        $escolhas = [];
        foreach ($model->bookingAnimalQuartos as $l) {
            $escolhas[$l->id_animal] = [
                'quarto' => (int) $l->id_quarto,
                'servicos' => array_map(static fn ($s) => (int) $s->id, $l->servicos),
                'proprias' => $l->datainicio !== null || $l->datafim !== null,
                'inicio' => $l->getEntrada(),
                'fim' => $l->getSaida(),
            ];
        }

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->status = Booking::STATUS_PENDENTE; // o formulário não pode mudar o estado
            $escolhas = $this->escolhasDoPedido();
            if ($this->guardar($model, $escolhas)) {
                Yii::$app->session->setFlash('success', 'Pedido de reserva atualizado.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', $this->dadosFormulario($model, $escolhas));
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
     * Lê o que o formulário enviou, por animal:
     *   quartos[id_animal] = id_quarto (vazio = não vem), servicos[id_animal][] = id_servico,
     *   datas[id_animal][proprias|inicio|fim] (datas próprias, opcionais).
     *
     * @return array [id_animal => ['quarto' => int|null, 'servicos' => int[], 'proprias' => bool, 'inicio' => ?string, 'fim' => ?string]]
     */
    private function escolhasDoPedido(): array
    {
        $req = $this->request;
        $quartos = (array) $req->post('quartos', []);
        $servicos = (array) $req->post('servicos', []);
        $datas = (array) $req->post('datas', []);

        $escolhas = [];
        foreach (array_unique(array_merge(array_keys($quartos), array_keys($servicos), array_keys($datas))) as $idAnimal) {
            $d = (array) ($datas[$idAnimal] ?? []);
            $escolhas[(int) $idAnimal] = [
                'quarto' => ($quartos[$idAnimal] ?? '') !== '' ? (int) $quartos[$idAnimal] : null,
                'servicos' => array_values(array_unique(array_map('intval', array_filter((array) ($servicos[$idAnimal] ?? []))))),
                'proprias' => !empty($d['proprias']),
                'inicio' => ($d['inicio'] ?? '') ?: null,
                'fim' => ($d['fim'] ?? '') ?: null,
            ];
        }
        return $escolhas;
    }

    /**
     * Guarda a reserva e as linhas (um quarto por animal, com as datas e os serviços de cada um).
     * Em caso de erro, deixa uma mensagem flash e devolve false (nada fica gravado).
     */
    private function guardar(Booking $model, array $escolhas): bool
    {
        $nova = $model->isNewRecord;
        $vem = array_filter($escolhas, static fn ($e) => $e['quarto'] !== null);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($vem === []) {
                throw new \DomainException('Escolha pelo menos um animal e o quarto onde vai ficar.');
            }
            if ($nova && $model->datainicio < date('Y-m-d')) {
                $model->addError('datainicio', 'A data de entrada não pode ser no passado.');
            }
            if ($model->hasErrors() || !$model->validate()) {
                throw new \DomainException('Confirme as datas da estadia.');
            }

            // datas de cada animal: as próprias (dentro das da reserva) ou as da reserva
            $datasAnimal = [];
            $nomes = Animal::find()->select('nome')->where(['id' => array_keys($vem), 'id_user' => Yii::$app->user->id])->indexBy('id')->column();
            foreach ($vem as $idAnimal => $e) {
                if (!isset($nomes[$idAnimal])) {
                    throw new \DomainException('Animal inválido.');
                }
                $inicio = $e['proprias'] && $e['inicio'] ? $e['inicio'] : $model->datainicio;
                $fim = $e['proprias'] && $e['fim'] ? $e['fim'] : $model->datafim;
                if ($inicio < $model->datainicio || $fim > $model->datafim) {
                    throw new \DomainException('As datas de ' . $nomes[$idAnimal] . ' têm de ficar dentro das datas da reserva.');
                }
                if ($fim <= $inicio) {
                    throw new \DomainException('A saída de ' . $nomes[$idAnimal] . ' tem de ser depois da entrada.');
                }
                $datasAnimal[$idAnimal] = [$inicio, $fim];
            }

            // o mesmo animal não pode estar em duas estadias ao mesmo tempo
            if ($conflitos = Booking::sobreposicoes($datasAnimal, $nova ? null : (int) $model->id)) {
                throw new \DomainException(implode(' ', $conflitos));
            }

            if (!$model->save(false)) {
                throw new \DomainException('Não foi possível guardar a reserva.');
            }

            // apaga as ligações antigas e recria (serviços primeiro, por causa da FK)
            $idsAntigos = BookingAnimalQuarto::find()->select('id')->where(['id_booking' => $model->id])->column();
            if ($idsAntigos) {
                BookingAnimalServico::deleteAll(['id_booking_animal' => $idsAntigos]);
            }
            BookingAnimalQuarto::deleteAll(['id_booking' => $model->id]);

            $servicosExistentes = ServicosExtras::find()->select('id')->column();
            foreach ($vem as $idAnimal => $e) {
                if (Quarto::findOne($e['quarto']) === null) {
                    throw new \DomainException('Quarto inválido.');
                }
                [$inicio, $fim] = $datasAnimal[$idAnimal];
                $proprias = $inicio !== $model->datainicio || $fim !== $model->datafim;

                $baq = new BookingAnimalQuarto([
                    'id_booking' => $model->id,
                    'id_animal'  => $idAnimal,
                    'id_quarto'  => $e['quarto'],
                    'datainicio' => $proprias ? $inicio : null,
                    'datafim'    => $proprias ? $fim : null,
                ]);
                if (!$baq->save(false)) {
                    throw new \DomainException('Não foi possível guardar o quarto.');
                }

                foreach (array_intersect($e['servicos'], array_map('intval', $servicosExistentes)) as $idServico) {
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

    private function dadosFormulario(Booking $model, array $escolhas): array
    {
        return [
            'model'    => $model,
            'quartos'  => Quarto::find()->orderBy(['preco' => SORT_ASC])->all(),
            'animais'  => Animal::find()->with(['racaPrincipal', 'racaSecundaria'])->where(['id_user' => Yii::$app->user->id])->orderBy(['nome' => SORT_ASC])->all(),
            'servicos' => ServicosExtras::find()->orderBy(['preco' => SORT_ASC])->all(),
            'escolhas' => $escolhas,
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
