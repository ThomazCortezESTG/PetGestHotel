<?php

use common\models\Raca;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Animal $model */
/** @var yii\widgets\ActiveForm $form */

// Lista de raças agrupada por tipo (Cão, Gato, Outros)
$racas = ArrayHelper::map(
        Raca::find()->orderBy(['tipo' => SORT_ASC, 'nome' => SORT_ASC])->all(),
        'id',
        'nome',
        'tipo'
);
?>

<div class="animal-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'nome')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'data')->widget(\kartik\date\DatePicker::class, [
            'options' => ['placeholder' => 'Escolhe a data'],
            'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,
                    'startDate' => '1990-01-01',
                    'endDate' => '+10y',
                    'startView' => 0,
            ],
    ]) ?>

    <?= $form->field($model, 'id_raca_principal')->dropDownList($racas, [
            'prompt' => 'Escolhe a raça principal',
            'required' => true,
    ]) ?>

    <?= $form->field($model, 'id_raca_secundaria')->dropDownList($racas, [
            'prompt' => 'Sem raça secundária',
    ]) ?>

    <?= $form->field($model, 'vacinas')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'observacoes')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Guardar', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>