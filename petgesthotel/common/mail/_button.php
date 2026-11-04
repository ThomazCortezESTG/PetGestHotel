<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $label */
/** @var string $url */

?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
    <tr>
        <td style="background-color: #33566A; border-radius: 8px;">
            <?= Html::a(Html::encode($label), $url, [
                'style' => 'display: inline-block; padding: 14px 28px; font-family: Arial, Helvetica, sans-serif; font-size: 16px; color: #FFFFFF; text-decoration: none;',
            ]) ?>
        </td>
    </tr>
</table>
