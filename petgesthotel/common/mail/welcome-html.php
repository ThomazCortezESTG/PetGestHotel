<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */

$loginLink = Yii::$app->urlManager->createAbsoluteUrl(['site/login']);
?>
<div class="welcome">
    <p style="font-size: 20px; font-weight: bold;">Bem-vindo ao <?= Html::encode(Yii::$app->name) ?>, <?= Html::encode($user->username) ?>!</p>

    <p>A sua conta já está ativa. Pode entrar com o seu e-mail ou com o nome de utilizador.</p>

    <?= $this->render('_button', ['label' => 'Entrar na conta', 'url' => $loginLink]) ?>

    <p>Até breve, a si e aos seus animais!</p>
</div>
