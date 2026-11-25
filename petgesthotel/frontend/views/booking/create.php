<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Booking $model */
/** @var common\models\Quarto[] $quartos */
/** @var common\models\Animal[] $animais */
/** @var common\models\ServicosExtras[] $servicos */

$this->title = 'Create Booking';
$this->params['breadcrumbs'][] = ['label' => 'Bookings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="booking-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($flash = Yii::$app->session->getFlash('error')): ?>
        <div class="alert alert-danger"><?= Html::encode($flash) ?></div>
    <?php endif; ?>

    <?= $this->render('_form', [
            'model'    => $model,
            'quartos'  => $quartos,
            'animais'  => $animais,
            'servicos' => $servicos,
    ]) ?>

</div>
