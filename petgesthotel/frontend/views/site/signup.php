<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\SignupForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Crie a sua conta';
$this->params['meta_description'] = 'Crie a sua conta PetGestHotel.';
$this->params['authImage'] = 'cat.webp';
$this->params['authVariant'] = 'compact';
?>
<h1 class="pg-auth__title"><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

    <?= $form->field($model, 'username')
        ->textInput(['autofocus' => true, 'placeholder' => 'Nome de utilizador', 'autocomplete' => 'username'])
        ->label('Nome de utilizador:') ?>

    <?= $form->field($model, 'email')
        ->textInput(['type' => 'email', 'placeholder' => 'E-mail', 'autocomplete' => 'email'])
        ->label('E-mail:') ?>

    <?= $form->field($model, 'password')
        ->passwordInput([
            'placeholder' => 'Mínimo de ' . Yii::$app->params['user.passwordMinLength'] . ' caracteres',
            'autocomplete' => 'new-password',
        ])
        ->label('Password:') ?>

    <?= $form->field($model, 'password_repeat')
        ->passwordInput(['placeholder' => 'Repita a password', 'autocomplete' => 'new-password'])
        ->label('Repita a password:') ?>

    <?= Html::submitButton('Criar Conta', ['class' => 'pg-auth__btn', 'name' => 'signup-button']) ?>

<?php ActiveForm::end(); ?>

<p class="pg-auth__back"><?= Html::a('Já tenho conta', ['site/login']) ?></p>
