<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\ResetPasswordForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Defina a nova password';
$this->params['meta_description'] = 'Defina uma nova password para a sua conta PetGestHotel.';
$this->params['authImage'] = 'furao.jpeg';
?>
<h1 class="pg-auth__title"><?= Html::encode($this->title) ?></h1>
<p class="pg-auth__lead">Escolha uma password nova para a sua conta.</p>

<?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>

    <?= $form->field($model, 'password')
        ->passwordInput(['autofocus' => true, 'placeholder' => 'Nova password', 'autocomplete' => 'new-password'])
        ->label('Nova password:') ?>

    <?= Html::submitButton('Guardar', ['class' => 'pg-auth__btn']) ?>

<?php ActiveForm::end(); ?>
