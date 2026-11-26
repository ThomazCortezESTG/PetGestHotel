<?php

declare(strict_types=1);

/**
 * Pedir uma reserva.
 *
 * @var yii\web\View $this
 * @var common\models\Booking $model
 * @var common\models\Quarto[] $quartos
 * @var common\models\Animal[] $animais
 * @var common\models\ServicosExtras[] $servicos
 * @var array $escolhas [id_animal => escolhas desse animal]
 */

use frontend\assets\BookingAsset;
use yii\helpers\Url;

BookingAsset::register($this);

$this->title = 'Nova reserva | PetGestHotel';
?>

<div class="pg-acct pg-acct--edit pg-bk">
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="bk-title">
        <div class="pg-acct__wrap">
            <a class="pg-acct__back" href="<?= Url::to(['index']) ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                As minhas reservas
            </a>
            <h1 class="pg-acct__title" id="bk-title">Nova reserva</h1>
            <p class="pg-acct__lead">Escolha as datas, o quarto de cada animal e, se quiser, serviços extra. O hotel confirma o pedido.</p>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'reservas']) ?>
        <div class="pg-acct__main">
            <?= $this->render('_form', compact('model', 'quartos', 'animais', 'servicos', 'escolhas')) ?>
        </div>
    </div>
</div>
