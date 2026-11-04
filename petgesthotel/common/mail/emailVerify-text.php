<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */

$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);
?>
Olá <?= $user->username ?>,

Obrigado por se registar no <?= Yii::$app->name ?>! Para ativar a sua conta, confirme o seu e-mail no link abaixo:

<?= $verifyLink ?>


Se não foi você a criar esta conta, pode ignorar este e-mail.
