<?php

namespace backend\modules\api\controllers;

use yii\rest\ActiveController;

class AnimalController extends ActiveController
{
    public $modelClass = 'common\models\Animal';
}
