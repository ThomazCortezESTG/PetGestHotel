<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
$minutes = (int) (Yii::$app->params['user.passwordResetTokenExpire'] / 60);
?>
Olá <?= $user->username ?>,

Recebemos um pedido para recuperar a password da sua conta. Use o link abaixo para escolher uma nova (válido durante <?= $minutes ?> minutos):

<?= $resetLink ?>


Se não foi você a fazer este pedido, pode ignorar este e-mail: a sua password não muda.
