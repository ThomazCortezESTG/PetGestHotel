<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Booking;
use common\models\Conversa;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Reservas no back-office: o hotel aceita ou rejeita os pedidos dos clientes e faz o check-in e o check-out.
 *
 * Ao aceitar, escolhe-se o colaborador responsável (abre-se o chat com o cliente):
 * - um colaborador fica ele próprio responsável;
 * - um admin (permissão assignChat) pode escolher outra pessoa da equipa (a lista mostra quantas
 *   reservas ativas cada um já acompanha).
 */
class BookingController extends Controller
{
    public const FILTROS = [
        'pendentes' => [Booking::STATUS_PENDENTE],
        'ativas' => [Booking::STATUS_CONFIRMADA, Booking::STATUS_EM_CURSO],
        'terminadas' => [Booking::STATUS_CONCLUIDA, Booking::STATUS_REJEITADA, Booking::STATUS_CANCELADA],
        'todas' => [],
    ];

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['index', 'view'], 'roles' => ['viewBooking']],
                    ['allow' => true, 'actions' => ['aceitar'], 'roles' => ['approveBooking']],
                    ['allow' => true, 'actions' => ['rejeitar'], 'roles' => ['rejectBooking']],
                    ['allow' => true, 'actions' => ['checkin'], 'roles' => ['checkIn']],
                    ['allow' => true, 'actions' => ['checkout'], 'roles' => ['checkOut']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'aceitar' => ['POST'],
                    'rejeitar' => ['POST'],
                    'checkin' => ['POST'],
                    'checkout' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(string $filtro = 'pendentes')
    {
        if (!isset(self::FILTROS[$filtro])) {
            $filtro = 'pendentes';
        }

        $query = Booking::find()
            ->with(['user.userProfile', 'animais', 'conversa.colaborador.userProfile'])
            ->orderBy($filtro === 'pendentes' ? ['datainicio' => SORT_ASC] : ['datainicio' => SORT_DESC]);
        if (self::FILTROS[$filtro] !== []) {
            $query->where(['status' => self::FILTROS[$filtro]]);
        }

        $contagens = [];
        foreach (self::FILTROS as $chave => $estados) {
            $contagens[$chave] = $estados === [] ? null : (int) Booking::find()->where(['status' => $estados])->count();
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 30], 'sort' => false]),
            'filtro' => $filtro,
            'contagens' => $contagens,
        ]);
    }

    public function actionView(int $id)
    {
        $booking = $this->findBooking($id);
        $podeEscolher = Yii::$app->user->can('assignChat');

        return $this->render('view', [
            'booking' => $booking,
            'podeEscolher' => $podeEscolher,
            'colaboradores' => $podeEscolher ? $this->colaboradoresComCarga() : [],
        ]);
    }

    public function actionAceitar(int $id): Response
    {
        $booking = $this->findBooking($id);
        $eu = (int) Yii::$app->user->id;
        $responsavel = $eu;
        // só quem pode reatribuir conversas escolhe outra pessoa
        if (Yii::$app->user->can('assignChat')) {
            $responsavel = (int) Yii::$app->request->post('id_colaborador', $eu) ?: $eu;
        }

        if ($booking->confirmar($responsavel)) {
            $quem = $responsavel === $eu ? 'Fica responsável' : Conversa::pessoa($booking->conversa?->colaborador)['nome'] . ' fica responsável';
            Yii::$app->session->setFlash('success', 'Reserva #' . $booking->id . ' aceite. ' . $quem . ' pela conversa com o cliente, que já foi avisado.');
        } else {
            Yii::$app->session->setFlash('error', $booking->getFirstError('status') ?: 'Não foi possível aceitar a reserva.');
        }
        return $this->redirect(['view', 'id' => $booking->id]);
    }

    public function actionRejeitar(int $id): Response
    {
        $booking = $this->findBooking($id);
        if ($booking->rejeitar((string) Yii::$app->request->post('motivo', ''))) {
            Yii::$app->session->setFlash('success', 'Reserva #' . $booking->id . ' rejeitada. O cliente foi avisado com o motivo.');
        } else {
            $erros = $booking->getFirstErrors();
            Yii::$app->session->setFlash('error', reset($erros) ?: 'Não foi possível rejeitar a reserva.');
        }
        return $this->redirect(['view', 'id' => $booking->id]);
    }

    public function actionCheckin(int $id): Response
    {
        $booking = $this->findBooking($id);
        $booking->checkIn()
            ? Yii::$app->session->setFlash('success', 'Check-in feito. A reserva está em curso.')
            : Yii::$app->session->setFlash('error', $booking->getFirstError('status'));
        return $this->redirect(['view', 'id' => $booking->id]);
    }

    public function actionCheckout(int $id): Response
    {
        $booking = $this->findBooking($id);
        $booking->checkOut()
            ? Yii::$app->session->setFlash('success', 'Check-out feito. A estadia terminou e a conversa ficou só para leitura.')
            : Yii::$app->session->setFlash('error', $booking->getFirstError('status'));
        return $this->redirect(['view', 'id' => $booking->id]);
    }

    /**
     * Para o dropdown de responsável: [id => "Ana Silva (2 ativas)"], com quem aceita em primeiro.
     */
    private function colaboradoresComCarga(): array
    {
        $carga = Conversa::cargaPorColaborador();
        $eu = (int) Yii::$app->user->id;
        $lista = [];
        foreach (Conversa::colaboradores() as $id => $nome) {
            $n = $carga[$id] ?? 0;
            $lista[$id] = $nome . ($id === $eu ? ' (eu)' : '') . ' · ' . ($n === 1 ? '1 reserva ativa' : $n . ' reservas ativas');
        }
        if (isset($lista[$eu])) {
            $lista = [$eu => $lista[$eu]] + $lista;
        }
        return $lista;
    }

    private function findBooking(int $id): Booking
    {
        $booking = Booking::find()
            ->with(['user.userProfile', 'bookingAnimalQuartos.animal', 'bookingAnimalQuartos.quarto', 'bookingAnimalQuartos.servicos', 'conversa.colaborador.userProfile'])
            ->where(['id' => $id])
            ->one();
        if ($booking === null) {
            throw new NotFoundHttpException('Esta reserva não existe.');
        }
        return $booking;
    }
}
