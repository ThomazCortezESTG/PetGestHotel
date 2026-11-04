<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */

$loginLink = Yii::$app->urlManager->createAbsoluteUrl(['site/login']);
?>
Bem-vindo ao <?= Yii::$app->name ?>, <?= $user->username ?>!

A sua conta já está ativa. Pode entrar com o seu e-mail ou com o nome de utilizador:

<?= $loginLink ?>


Até breve, a si e aos seus animais!
