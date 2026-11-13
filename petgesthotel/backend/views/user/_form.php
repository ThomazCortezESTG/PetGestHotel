<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var common\models\UserProfile $profile */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?php if ($model->isNewRecord): ?>
        <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>
    <?php endif; ?>

    <?= $form->field($profile, 'nome')->textInput(['maxlength' => true]) ?>

    <?= $form->field($profile, 'n_contribuinte')->textInput(['maxlength' => true]) ?>

    <?= $form->field($profile, 'telefone')->textInput(['maxlength' => true]) ?>

    <?= $form->field($profile, 'data_nascimento')->input('date') ?>

    <?= $form->field($profile, 'morada')->textInput(['maxlength' => true]) ?>

    <?php if (!$model->isNewRecord): ?>
        <?= $form->field($model, 'status')->dropDownList([
                10 => 'Ativo',
                9 => 'Inativo',
                0 => 'Apagado (Soft Delete)',
        ]) ?>
    <?php endif; ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Guardar', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
