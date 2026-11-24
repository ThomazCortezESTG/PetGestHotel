<?php

declare(strict_types=1);

namespace backend\modules\api\controllers;

use backend\modules\api\models\Animal;
use common\components\PetStorage;
use common\models\AnimalDocumento;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Animais do utilizador autenticado. Nunca mostra nem altera animais de outras pessoas
 * (o id de um animal alheio dá 404, como no front-office).
 *
 * GET    api/animais                         -> lista (aceita ?expand=documentos)
 * GET    api/animais/{id}                    -> um animal (aceita ?expand=documentos)
 * POST   api/animais                         -> criar
 * PUT    api/animais/{id}                    -> editar
 * DELETE api/animais/{id}                    -> apagar (409 se já tiver reservas)
 * GET    api/animais/{id}/documentos         -> master-detail: o animal e os seus documentos de vacinas
 * GET    api/animais/{id}/documentos/{doc}   -> ficheiro do documento (PDF ou imagem)
 * GET    api/animais/{id}/foto               -> fotografia do animal
 */
class AnimalController extends ActiveController
{
    public $modelClass = Animal::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = ['class' => HttpBearerAuth::class];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return parent::verbs() + [
            'documentos' => ['GET', 'HEAD'],
            'documento' => ['GET', 'HEAD'],
            'foto' => ['GET', 'HEAD'],
        ];
    }

    public function actions(): array
    {
        $actions = parent::actions();
        // apagar tem uma regra própria (reservas), ver actionDelete
        unset($actions['delete']);

        $actions['index']['prepareDataProvider'] = fn () => new ActiveDataProvider([
            'query' => Animal::find()
                ->with(['racaPrincipal', 'racaSecundaria'])
                ->where(['id_user' => Yii::$app->user->id])
                ->orderBy(['nome' => SORT_ASC]),
            'pagination' => false,
        ]);

        return $actions;
    }

    /**
     * Chamado pelas ações view/update do ActiveController: só o dono tem acesso.
     */
    public function checkAccess($action, $model = null, $params = []): void
    {
        if ($model !== null && (int) $model->id_user !== (int) Yii::$app->user->id) {
            throw new NotFoundHttpException('Este animal não existe ou não está registado na sua conta.');
        }
    }

    public function actionDelete($id): void
    {
        $model = $this->findAnimal($id);

        // Um animal com reservas não pode ser apagado: as reservas e as faturas precisam dele
        $temReservas = (new \yii\db\Query())
            ->from('{{%booking_animal_quarto}}')
            ->where(['id_animal' => $model->id])
            ->exists();
        if ($temReservas) {
            throw new ConflictHttpException($model->nome . ' já tem reservas, por isso não pode ser removido.');
        }

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Não foi possível remover o animal.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }

    /**
     * Master-detail: dados do animal + lista dos documentos de vacinas.
     */
    public function actionDocumentos($id): array
    {
        $model = $this->findAnimal($id);

        return [
            'animal' => $model,
            'documentos' => array_map([Animal::class, 'documentoData'], $model->documentos),
        ];
    }

    public function actionDocumento($id, $doc): Response
    {
        $model = $this->findAnimal($id);
        $documento = AnimalDocumento::findOne(['id' => $doc, 'id_animal' => $model->id]);
        $path = $documento ? PetStorage::path($documento->ficheiro) : null;
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Este documento não existe.');
        }

        return $this->enviarFicheiro($path, $documento->nome_original, $documento->mime);
    }

    public function actionFoto($id): Response
    {
        $model = $this->findAnimal($id);
        $path = PetStorage::path($model->foto);
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Este animal não tem fotografia.');
        }

        return $this->enviarFicheiro($path, $model->foto, str_ends_with($model->foto, '.webp') ? 'image/webp' : 'image/jpeg');
    }

    private function findAnimal($id): Animal
    {
        $model = Animal::find()
            ->with(['racaPrincipal', 'racaSecundaria'])
            ->where(['id' => $id, 'id_user' => Yii::$app->user->id])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException('Este animal não existe ou não está registado na sua conta.');
        }
        return $model;
    }

    private function enviarFicheiro(string $path, string $nome, string $mime): Response
    {
        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', 'private, max-age=86400');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response->sendFile($path, $nome, ['mimeType' => $mime, 'inline' => true]);
    }
}
