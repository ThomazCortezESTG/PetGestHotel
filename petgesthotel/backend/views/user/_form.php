<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var common\models\UserProfile $profile */
/** @var string|null $papel */
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

    <?php $proprio = !$model->isNewRecord && (int) $model->id === (int) Yii::$app->user->id; ?>
    <div class="form-group">
        <label for="user-papel">Papel</label>
        <?= Html::dropDownList('papel', $papel ?? \common\components\Papeis::CLIENTE, \common\components\Papeis::lista(), [
            'id' => 'user-papel',
            'class' => 'form-control',
            'disabled' => $proprio,
        ]) ?>
        <small class="form-text text-muted">
            <?= $proprio
                ? 'Não pode mudar o seu próprio papel. Peça a outro admin.'
                : 'Colaborador: aceita reservas e fala com os clientes. Admin: tudo o que o colaborador faz, mais gerir utilizadores e reatribuir conversas.' ?>
        </small>
        <?php if ($proprio): ?>
            <?= Html::hiddenInput('papel', $papel ?? '') ?>
        <?php endif; ?>
    </div>

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
