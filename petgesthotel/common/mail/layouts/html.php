<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var \yii\web\View $this view component instance */
/** @var \yii\symfonymailer\Message $message the message being composed */
/** @var string $content main view render result */

$logo = $message->embed(Yii::getAlias('@common/mail/images/logo.png'));
$hotel = Yii::$app->params['hotel'] ?? null;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body style="margin: 0; padding: 0; background-color: #F1F6F7;">
    <?php $this->beginBody() ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F1F6F7;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #FFFFFF; border-radius: 12px;">
                    <tr>
                        <td style="padding: 28px 32px 0;">
                            <img src="<?= $logo ?>" alt="<?= Html::encode(Yii::$app->name) ?>" width="200" style="display: block; border: 0;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 32px 32px; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.5; color: #0F2F47;">
                            <?= $content ?>
                        </td>
                    </tr>
                </table>
                <?php if ($hotel !== null): ?>
                    <p style="margin: 16px 0 0; font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.5; color: #33506A;">
                        <?= Html::encode($hotel['morada']) ?>, <?= Html::encode($hotel['codigoPostal']) ?><br />
                        <?= Html::encode($hotel['telefone']) ?> · <?= Html::encode($hotel['email']) ?>
                    </p>
                <?php endif ?>
            </td>
        </tr>
    </table>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
