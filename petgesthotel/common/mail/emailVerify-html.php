<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */

$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);
?>
<div class="verify-email">
    <p>Olá <?= Html::encode($user->username) ?>,</p>

    <p>Obrigado por se registar no <?= Html::encode(Yii::$app->name) ?>! Para ativar a sua conta, confirme o seu e-mail:</p>

    <?= $this->render('_button', ['label' => 'Confirmar e-mail', 'url' => $verifyLink]) ?>

    <p style="font-size: 13px; color: #33506A;">Se o botão não funcionar, copie este link para o browser:<br /><?= Html::a(Html::encode($verifyLink), $verifyLink) ?></p>

    <p style="font-size: 13px; color: #33506A;">Se não foi você a criar esta conta, pode ignorar este e-mail.</p>
</div>
