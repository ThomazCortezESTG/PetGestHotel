<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
$minutes = (int) (Yii::$app->params['user.passwordResetTokenExpire'] / 60);
?>
<div class="password-reset">
    <p>Olá <?= Html::encode($user->username) ?>,</p>

    <p>Recebemos um pedido para recuperar a password da sua conta. Use o botão abaixo para escolher uma nova:</p>

    <?= $this->render('_button', ['label' => 'Definir nova password', 'url' => $resetLink]) ?>

    <p style="font-size: 13px; color: #33506A;">O link é válido durante <?= $minutes ?> minutos. Se o botão não funcionar, copie-o para o browser:<br /><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>

    <p style="font-size: 13px; color: #33506A;">Se não foi você a fazer este pedido, pode ignorar este e-mail: a sua password não muda.</p>
</div>
