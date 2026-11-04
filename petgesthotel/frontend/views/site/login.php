<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Entre na sua conta';
$this->params['meta_description'] = 'Entre na sua conta PetGestHotel.';
$this->params['authImage'] = 'borzoiDog.webp';
?>
<h1 class="pg-auth__title"><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

    <?= $form->field($model, 'username')
        ->textInput(['autofocus' => true, 'placeholder' => 'E-mail ou nome de utilizador', 'autocomplete' => 'username'])
        ->label('E-mail ou nome de utilizador:') ?>

    <?= $form->field($model, 'password')
        ->passwordInput(['placeholder' => 'Password', 'autocomplete' => 'current-password'])
        ->label('Password:') ?>

    <div class="pg-auth__options">
        <?= $form->field($model, 'rememberMe')->checkbox() ?>
        <?= Html::a('Esqueceu-se da password?', ['site/request-password-reset']) ?>
    </div>

    <?php if ($model->hasErrors()): ?>
        <p class="pg-auth__forgot">
            Ainda não confirmou o e-mail?
            <?= Html::a('Reenviar confirmação', ['site/resend-verification-email']) ?>
        </p>
    <?php endif ?>

    <?= Html::submitButton('Entrar', ['class' => 'pg-auth__btn', 'name' => 'login-button']) ?>

<?php ActiveForm::end(); ?>

<p class="pg-auth__or">ou</p>

<?= Html::a('Criar Conta', ['site/signup'], ['class' => 'pg-auth__btn pg-auth__btn--dark']) ?>
