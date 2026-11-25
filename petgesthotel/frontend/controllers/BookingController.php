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
use frontend\models\BookingSearch;

/**
 * BookingController implements the CRUD actions for Booking model.
 */
class BookingController extends Controller
{
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
                        ['allow' => true, 'roles' => ['@']],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Booking models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new BookingSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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
     * Creates a new Booking model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model    = new Booking();
        $quartos  = Quarto::find()->all();
        $animais  = Animal::find()->where(['id_user' => Yii::$app->user->id])->all();
        $servicos = ServicosExtras::find()->all();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $escolhas  = array_filter(Yii::$app->request->post('quartos', [])); // [id_animal => id_quarto]
                $idServico = Yii::$app->request->post('id_servico');

                $model->id_user = Yii::$app->user->id;
                $model->status  = 'pendente';
                $model->preco   = 0; // recalculado depois

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if (empty($escolhas)) {
                        throw new \Exception('Seleciona pelo menos um animal e um quarto.');
                    }
                    if (count($escolhas) !== count(array_unique($escolhas))) {
                        throw new \Exception('Cada animal tem de ter um quarto diferente.');
                    }
                    if (!$model->save()) {
                        throw new \Exception('Erro ao guardar a reserva.');
                    }

                    foreach ($escolhas as $idAnimal => $idQuarto) {
                        $animal = Animal::findOne(['id' => $idAnimal, 'id_user' => Yii::$app->user->id]);
                        $quarto = Quarto::findOne($idQuarto);
                        if (!$animal || !$quarto) {
                            throw new \Exception('Animal ou quarto inválido.');
                        }

                        $baq = new BookingAnimalQuarto([
                            'id_booking' => $model->id,
                            'id_animal'  => $animal->id,
                            'id_quarto'  => $quarto->id,
                        ]);
                        $baq->save(false);

                        if ($idServico) {
                            $bas = new BookingAnimalServico([
                                'id_booking_animal' => $baq->id,
                                'id_servico'        => $idServico,
                            ]);
                            $bas->save(false);
                        }
                    }

                    $model->calcularPreco();
                    $transaction->commit();

                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model'    => $model,
            'quartos'  => $quartos,
            'animais'  => $animais,
            'servicos' => $servicos,
        ]);
    }

    /**
     * Updates an existing Booking model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model    = $this->findModel($id);
        $quartos  = Quarto::find()->all();
        $animais  = Animal::find()->where(['id_user' => Yii::$app->user->id])->all();
        $servicos = ServicosExtras::find()->all();

        // escolhas já guardadas
        $linhas = BookingAnimalQuarto::find()->where(['id_booking' => $model->id])->all();

        $escolhasQuartos = [];   // [id_animal => id_quarto]
        foreach ($linhas as $l) {
            $escolhasQuartos[$l->id_animal] = $l->id_quarto;
        }

        $escolhasServicos = BookingAnimalServico::find()
            ->select('id_servico')
            ->where(['id_booking_animal' => array_map(fn($l) => $l->id, $linhas)])
            ->distinct()
            ->column();

        if ($this->request->isPost && $model->load($this->request->post())) {
            $postQuartos = array_filter(Yii::$app->request->post('quartos', []));
            $postServicos = array_unique(array_filter((array) Yii::$app->request->post('servicos', [])));

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (empty($postQuartos)) {
                    throw new \Exception('Seleciona pelo menos um animal e um quarto.');
                }
                if (count($postQuartos) !== count(array_unique($postQuartos))) {
                    throw new \Exception('Cada animal tem de ter um quarto diferente.');
                }
                if (!$model->save()) {
                    throw new \Exception('Erro ao guardar a reserva.');
                }

                // apaga as ligações antigas e recria (serviços primeiro, por causa da FK)
                $idsAntigos = array_map(fn($l) => $l->id, $linhas);
                if ($idsAntigos) {
                    BookingAnimalServico::deleteAll(['id_booking_animal' => $idsAntigos]);
                }
                BookingAnimalQuarto::deleteAll(['id_booking' => $model->id]);

                foreach ($postQuartos as $idAnimal => $idQuarto) {
                    $animal = Animal::findOne(['id' => $idAnimal, 'id_user' => Yii::$app->user->id]);
                    $quarto = Quarto::findOne($idQuarto);
                    if (!$animal || !$quarto) {
                        throw new \Exception('Animal ou quarto inválido.');
                    }

                    $baq = new BookingAnimalQuarto([
                        'id_booking' => $model->id,
                        'id_animal'  => $animal->id,
                        'id_quarto'  => $quarto->id,
                    ]);
                    if (!$baq->save(false)) {
                        throw new \Exception('Erro ao guardar o quarto.');
                    }

                    foreach ($postServicos as $idServico) {
                        $bas = new BookingAnimalServico([
                            'id_booking_animal' => $baq->id,
                            'id_servico'        => $idServico,
                        ]);
                        $bas->save(false);
                    }
                }

                $model->calcularPreco();
                $transaction->commit();
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
                // em caso de erro, mostra o que o utilizador acabou de escolher
                $escolhasQuartos  = $postQuartos;
                $escolhasServicos = $postServicos;
            }
        }

        return $this->render('update', [
            'model'            => $model,
            'quartos'          => $quartos,
            'animais'          => $animais,
            'servicos'         => $servicos,
            'escolhasQuartos'  => $escolhasQuartos,
            'escolhasServicos' => $escolhasServicos,
        ]);
    }

    /**
     * Deletes an existing Booking model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Booking model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Booking the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Booking::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
