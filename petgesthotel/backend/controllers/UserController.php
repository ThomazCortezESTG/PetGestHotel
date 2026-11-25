<?php

namespace backend\controllers;

use common\components\Papeis;
use common\models\Conversa;
use common\models\User;
use backend\models\UserSearch;
use common\models\UserProfile;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii;
use yii\filters\AccessControl;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
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
                        [
                            'allow' => true,
                            'roles' => ['admin'], // Troca 'admin' pelo nome exato do papel na tua tabela auth_item
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all User models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     * @param int $id
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
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new User(['scenario' => 'create']);
        $profile = new UserProfile();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $profile->load($this->request->post())) {
                if (!empty($model->password)) {
                    $model->setPassword($model->password);
                }
                $model->generateAuthKey();
                $model->status = 10;
                $papel = (string) $this->request->post('papel', Papeis::CLIENTE);

                if (isset(Papeis::lista()[$papel]) && $model->save()) {
                    $profile->user_id = $model->id;
                    if ($profile->save()) {
                        $auth = Yii::$app->authManager;
                        $auth->assign($auth->getRole($papel), $model->id);
                        Yii::$app->session->setFlash('success', 'Utilizador criado com sucesso (' . Papeis::nome($papel) . ').');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'profile' => $profile,
            'papel' => (string) $this->request->post('papel', Papeis::CLIENTE),
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        // Carrega o perfil existente ou cria um novo caso o utilizador ainda não tenha perfil
        $profile = $model->userProfile ?? new UserProfile(['user_id' => $model->id]);

        $papelAtual = Papeis::de($model->id);
        $papel = (string) $this->request->post('papel', $papelAtual ?? Papeis::CLIENTE);

        if ($this->request->isPost && $model->load($this->request->post()) && $profile->load($this->request->post())) {
            if ($model->save() && $profile->save()) {
                try {
                    Papeis::mudar($model->id, $papel, (int) Yii::$app->user->id);
                    $aviso = '';
                    if ($papelAtual !== $papel) {
                        $aviso = ' Papel: ' . Papeis::nome($papel) . '.';
                    }
                    // desativado: deixa de poder acompanhar reservas
                    if ((int) $model->status !== User::STATUS_ACTIVE && ($n = Conversa::largarResponsavel($model->id)) > 0) {
                        $aviso .= ' ' . $n . ' conversa(s) ficaram sem responsável.';
                    }
                    Yii::$app->session->setFlash('success', 'Utilizador atualizado com sucesso.' . $aviso);
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\InvalidArgumentException $e) {
                    Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'profile' => $profile,
            'papel' => $papel,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ((int) $model->id === (int) Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Não pode desativar a sua própria conta.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        $model->status = 0; // Marca como apagado (Soft Delete)
        $model->save(false); // Guarda sem forçar validação de password
        $n = Conversa::largarResponsavel($model->id);

        Yii::$app->session->setFlash('success', 'Utilizador desativado com sucesso.' . ($n > 0 ? ' ' . $n . ' conversa(s) ficaram sem responsável.' : ''));

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
