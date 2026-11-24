<?php

declare(strict_types=1);

/**
 * Conversa do cliente com o colaborador responsável por uma reserva.
 *
 * @var yii\web\View $this
 * @var common\models\Conversa $conversa
 * @var bool $temOutras
 */

use common\models\Conversa;
use frontend\assets\ConversaAsset;
use yii\helpers\Html;
use yii\helpers\Url;

ConversaAsset::register($this);

$booking = $conversa->booking;
$fmt = Yii::$app->formatter;
$animais = array_map(static fn ($a) => $a->nome, $booking->animais);
$responsavel = $conversa->colaborador ? Conversa::pessoa($conversa->colaborador) : null;

$this->title = 'Mensagens | PetGestHotel';
?>

<div class="pg-acct pg-msgs">
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="msgs-title">
        <div class="pg-acct__wrap">
            <h1 class="pg-acct__title" id="msgs-title">Mensagens</h1>
            <p class="pg-acct__lead">
                Estadia <?= $animais ? 'de ' . Html::encode(implode(', ', $animais)) : '' ?>,
                de <?= $fmt->asDate($booking->datainicio, 'd MMMM') ?> a <?= $fmt->asDate($booking->datafim, 'd MMMM') ?>.
            </p>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'mensagens']) ?>

        <div class="pg-acct__main">
            <?php if ($temOutras): ?>
                <p class="pg-msgs__back"><a href="<?= Url::to(['index']) ?>">&larr; Todas as conversas</a></p>
            <?php endif; ?>

            <header class="pg-msgs__head">
                <?= $this->render('_responsavel', ['conversa' => $conversa, 'class' => 'pg-msgs__avatar pg-msgs__avatar--lg']) ?>
                <div>
                    <?php if ($responsavel): ?>
                        <p class="pg-msgs__who"><?= Html::encode($responsavel['nome']) ?></p>
                        <p class="pg-msgs__role">Responsável pela sua reserva</p>
                    <?php else: ?>
                        <p class="pg-msgs__who">Equipa PetGestHotel</p>
                        <p class="pg-msgs__role">Ainda a atribuir um colaborador. Já pode escrever: a mensagem fica à espera.</p>
                    <?php endif; ?>
                </div>
                <span class="pg-msgs__state pg-msgs__state--<?= Html::encode($booking->status) ?>"><?= Html::encode($booking->getStatusLabel()) ?></span>
            </header>

            <?= $this->render('@common/views/chat/_chat', [
                'conversa' => $conversa,
                'urls' => [
                    'mensagens' => Url::to(['mensagens', 'id' => $conversa->id]),
                    'enviar' => Url::to(['enviar', 'id' => $conversa->id]),
                    'lidas' => Url::to(['lidas', 'id' => $conversa->id]),
                ],
            ]) ?>
        </div>
    </div>
</div>
