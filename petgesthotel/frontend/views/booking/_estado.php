<?php

declare(strict_types=1);

/**
 * Etiqueta do estado da reserva (cor por estado, ver booking.css).
 *
 * @var common\models\Booking $booking
 */

use yii\helpers\Html;

?>
<span class="pg-bk-state pg-bk-state--<?= Html::encode((string) $booking->status) ?>"><?= Html::encode($booking->getStatusLabel()) ?></span>
