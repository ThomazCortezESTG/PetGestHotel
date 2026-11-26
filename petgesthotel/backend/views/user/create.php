<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var common\models\UserProfile $profile */
/** @var string $papel */

$this->title = 'Create User';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
            'model' => $model,
            'profile' => $profile,
            'papel' => $papel,
    ]) ?>

</div>
