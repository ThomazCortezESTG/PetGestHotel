<?php

namespace frontend\controllers;

use common\components\PetStorage;
use common\models\Animal;
use common\models\AnimalDocumento;
use frontend\models\PetFicheirosForm;
use frontend\models\PetSearch;
use Yii;
use yii\web\Response;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * PetController implements the CRUD actions for Animal model.
 */
class PetController extends Controller
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
     * Um envio maior do que post_max_size chega ao PHP sem dados (nem o token CSRF) e daria um erro 400.
     * Aqui volta ao formulário com uma mensagem a explicar.
     */
    public function beforeAction($action)
    {
        $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($this->request->isPost && $_POST === [] && $_FILES === [] && $len > PetStorage::maxPostBytes()) {
            Yii::$app->session->setFlash('pet-error', 'Os ficheiros juntos passam de ' . PetStorage::humanSize(PetStorage::maxPostBytes())
                . '. Envie menos documentos de cada vez.');
            $this->redirect($this->request->referrer ?: ['index'])->send();
            return false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Lists all Animal models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new PetSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Animal model.
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
     * Creates a new Animal model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Animal();
        $model->loadDefaultValues();
        $ficheiros = new PetFicheirosForm($model);

        if ($this->request->isPost && $this->guardar($model, $ficheiros)) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'ficheiros' => $ficheiros,
        ]);
    }

    /**
     * Updates an existing Animal model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $ficheiros = new PetFicheirosForm($model);

        if ($this->request->isPost && $this->guardar($model, $ficheiros)) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'ficheiros' => $ficheiros,
        ]);
    }

    /**
     * Grava os dados e os ficheiros do animal numa só transação.
     * Se algum ficheiro falhar, nada fica gravado (nem os dados, nem os ficheiros já escritos).
     */
    private function guardar(Animal $model, PetFicheirosForm $ficheiros): bool
    {
        $model->load($this->request->post());
        $ficheiros->loadFromRequest();
        $okDados = $model->validate();
        $okFicheiros = $ficheiros->validate();
        if (!$okDados || !$okFicheiros) {
            return false;
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save(false)) {
                throw new \RuntimeException('Não foi possível guardar os dados.');
            }
            $ficheiros->apply();
            $tx->commit();
        } catch (\RuntimeException $e) {
            $tx->rollBack();
            $ficheiros->afterRollback();
            $ficheiros->addError('documentos', $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            $tx->rollBack();
            $ficheiros->afterRollback();
            throw $e;
        }
        $ficheiros->afterCommit();
        return true;
    }

    /**
     * Fotografia do animal. Só para o dono (findModel dá 404 a outros clientes).
     */
    public function actionFoto($id): Response
    {
        $model = $this->findModel($id);
        $path = PetStorage::path($model->foto);
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Este animal não tem fotografia.');
        }

        return $this->enviarFicheiro($path, $model->foto, str_ends_with($model->foto, '.webp') ? 'image/webp' : 'image/jpeg');
    }

    /**
     * Documento de vacinas (PDF ou imagem). Só para o dono do animal.
     */
    public function actionDocumento($id): Response
    {
        $doc = AnimalDocumento::find()
            ->alias('d')
            ->innerJoinWith('animal a', false)
            ->where(['d.id' => $id, 'a.id_user' => Yii::$app->user->id])
            ->one();
        $path = $doc ? PetStorage::path($doc->ficheiro) : null;
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Este documento não existe ou não é de um animal seu.');
        }

        return $this->enviarFicheiro($path, $doc->nome_original, $doc->mime);
    }

    private function enviarFicheiro(string $path, string $nome, string $mime): Response
    {
        $response = Yii::$app->response;
        // dados privados: o browser pode guardar, mas nenhum proxy pelo caminho
        $response->headers->set('Cache-Control', 'private, max-age=86400');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");

        return $response->sendFile($path, $nome, ['mimeType' => $mime, 'inline' => true]);
    }

    /**
     * Deletes an existing Animal model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Um animal com reservas não pode ser apagado: as reservas e as faturas precisam dele
        // (consulta direta à tabela: o modelo BookingAnimalQuarto ainda não existe)
        $temReservas = (new \yii\db\Query())
            ->from('{{%booking_animal_quarto}}')
            ->where(['id_animal' => $model->id])
            ->exists();
        if ($temReservas) {
            \Yii::$app->session->setFlash('pet-error', $model->nome . ' já tem reservas, por isso não pode ser removido.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Animal model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Animal the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        // Só encontra animais do próprio cliente: o id de um animal de outra pessoa dá 404
        $model = Animal::find()
            ->with(['racaPrincipal', 'racaSecundaria'])
            ->where(['id' => $id, 'id_user' => \Yii::$app->user->id])
            ->one();
        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Este animal não existe ou não está registado na sua conta.');
    }
}
