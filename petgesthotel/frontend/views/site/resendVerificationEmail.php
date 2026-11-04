<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\ResendVerificationEmailForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Reenviar e-mail de confirmação';
$this->params['meta_description'] = 'Reenvie o e-mail de confirmação da sua conta PetGestHotel.';
$this->params['authImage'] = 'cat.webp';
?>
<h1 class="pg-auth__title"><?= Html::encode($this->title) ?></h1>
<p class="pg-auth__lead">Insira o e-mail da sua conta e enviaremos um novo link de confirmação.</p>

<?php $form = ActiveForm::begin(['id' => 'resend-verification-email-form']); ?>

    <?= $form->field($model, 'email')
        ->textInput(['type' => 'email', 'autofocus' => true, 'placeholder' => 'E-mail', 'autocomplete' => 'email'])
        ->label('E-mail:') ?>

    <?= Html::submitButton('Enviar', ['class' => 'pg-auth__btn']) ?>

<?php ActiveForm::end(); ?>

<p class="pg-auth__back"><?= Html::a('Quero voltar ao Login', ['site/login']) ?></p>
