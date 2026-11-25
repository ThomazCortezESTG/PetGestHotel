<?php

declare(strict_types=1);

namespace backend\modules\api\controllers;

use common\models\Raca;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

/**
 * Raças (só leitura), para os campos de raça da app.
 *
 * GET api/racas              -> todas, por nome
 * GET api/racas?tipo=Gato    -> só de um tipo ("Cão", "Gato" ou "Outros")
 * GET api/racas/{id}
 */
class RacaController extends ActiveController
{
    public $modelClass = Raca::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = ['class' => HttpBearerAuth::class];
        return $behaviors;
    }

    public function actions(): array
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);

        $actions['index']['prepareDataProvider'] = function () {
            $query = Raca::find()->orderBy(['nome' => SORT_ASC]);
            $tipo = Yii::$app->request->get('tipo');
            if ($tipo !== null && $tipo !== '') {
                $query->andWhere(['tipo' => $tipo]);
            }
            return new ActiveDataProvider(['query' => $query, 'pagination' => false]);
        };

        return $actions;
    }
}
