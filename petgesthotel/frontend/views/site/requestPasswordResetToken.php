<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\PasswordResetRequestForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Esqueceu-se da password?';
$this->params['meta_description'] = 'Peça um e-mail para recuperar o acesso à sua conta PetGestHotel.';
$this->params['authImage'] = 'furao.jpeg';
?>
<h1 class="pg-auth__title"><?= Html::encode($this->title) ?></h1>
<p class="pg-auth__lead">Sem problema! Basta inserir o e-mail da sua conta e enviaremos um e-mail de recuperação!</p>

<?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>

    <?= $form->field($model, 'email')
        ->textInput(['type' => 'email', 'autofocus' => true, 'placeholder' => 'nome@exemplo.pt', 'autocomplete' => 'email'])
        ->label('E-mail') ?>

    <?= Html::submitButton('Enviar e-mail', ['class' => 'pg-auth__btn']) ?>

<?php ActiveForm::end(); ?>

<p class="pg-auth__back"><?= Html::a('Quero voltar ao Login', ['site/login']) ?></p>
