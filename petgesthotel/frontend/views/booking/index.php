<?php

declare(strict_types=1);

/**
 * As reservas do cliente, no estilo da área de cliente: próximas (pendentes, confirmadas, em curso)
 * e anteriores (terminadas, rejeitadas, canceladas).
 *
 * @var yii\web\View $this
 * @var common\models\Booking[] $proximas
 * @var common\models\Booking[] $anteriores
 * @var bool $temAnimais
 */

use common\models\Booking;
use common\models\Conversa;
use frontend\assets\BookingAsset;
use yii\helpers\Html;
use yii\helpers\Url;

BookingAsset::register($this);

$this->title = 'As minhas reservas | PetGestHotel';
$fmt = Yii::$app->formatter;
$total = count($proximas) + count($anteriores);

$cartao = function (Booking $b, int $i) use ($fmt): string {
    $nomes = array_map(static fn ($l) => $l->animal->nome, $b->bookingAnimalQuartos);
    $quartos = array_unique(array_map(static fn ($l) => ucfirst((string) ($l->quarto->categoria ?? '')), $b->bookingAnimalQuartos));
    $resp = $b->conversa?->colaborador;
    $url = Url::to(['view', 'id' => $b->id]);

    ob_start(); ?>
    <li class="pg-bk-card pg-bk-card--<?= Html::encode((string) $b->status) ?>" style="--i: <?= $i ?>">
        <a class="pg-bk-card__date" href="<?= $url ?>" tabindex="-1" aria-hidden="true">
            <span class="pg-bk-card__day"><?= $fmt->asDate($b->datainicio, 'd') ?></span>
            <span class="pg-bk-card__month"><?= Html::encode(rtrim($fmt->asDate($b->datainicio, 'MMM'), '.')) ?></span>
        </a>
        <div class="pg-bk-card__body">
            <h3 class="pg-bk-card__title">
                <a href="<?= $url ?>"><?= Html::encode($nomes ? 'Estadia de ' . implode(', ', $nomes) : 'Reserva #' . $b->id) ?></a>
            </h3>
            <p class="pg-bk-card__meta">
                <?= $fmt->asDate($b->datainicio, 'd MMM') ?> a <?= $fmt->asDate($b->datafim, 'd MMM yyyy') ?>
                · <?= $b->noites ?> <?= $b->noites === 1 ? 'noite' : 'noites' ?>
                <?php if ($quartos): ?> · Quarto <?= Html::encode(implode(' e ', $quartos)) ?><?php endif; ?>
            </p>
            <?php if ($b->status === Booking::STATUS_PENDENTE): ?>
                <p class="pg-bk-card__note">À espera de resposta do hotel.</p>
            <?php elseif ($resp && in_array($b->status, [Booking::STATUS_CONFIRMADA, Booking::STATUS_EM_CURSO], true)): ?>
                <p class="pg-bk-card__note">Acompanhada por <?= Html::encode(Conversa::pessoa($resp)['nome']) ?>.</p>
            <?php elseif ($b->status === Booking::STATUS_REJEITADA && $b->motivo_rejeicao): ?>
                <p class="pg-bk-card__note">Não aceite: <?= Html::encode($b->motivo_rejeicao) ?></p>
            <?php endif; ?>
        </div>
        <div class="pg-bk-card__side">
            <?= $this->render('_estado', ['booking' => $b]) ?>
            <strong class="pg-bk-card__price"><?= $b->preco !== null ? $fmt->asCurrency($b->preco, 'EUR') : '' ?></strong>
            <?php if ($b->conversa !== null && in_array($b->status, [Booking::STATUS_CONFIRMADA, Booking::STATUS_EM_CURSO], true)): ?>
                <a class="pg-bk-card__chat" href="<?= Url::to(['/conversa/view', 'id' => $b->conversa->id]) ?>">Mensagens</a>
            <?php endif; ?>
        </div>
    </li>
    <?php
    return (string) ob_get_clean();
};
?>

<div class="pg-acct pg-bk">
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="bk-title">
        <div class="pg-acct__wrap pg-pets__band">
            <div>
                <h1 class="pg-acct__title" id="bk-title">As minhas reservas</h1>
                <p class="pg-acct__lead">
                    <?php if ($proximas !== []): ?>
                        <?= count($proximas) === 1 ? 'Tem 1 estadia marcada ou a decorrer.' : 'Tem ' . count($proximas) . ' estadias marcadas ou a decorrer.' ?>
                    <?php else: ?>
                        Marque a próxima estadia do seu animal. O hotel confirma o pedido e fica a acompanhar.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($temAnimais && $total > 0): ?>
                <a class="pg-btn pg-btn--primary" href="<?= Url::to(['create']) ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Nova reserva
                </a>
            <?php endif; ?>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'reservas']) ?>

        <div class="pg-acct__main">
            <?php if ($total === 0): ?>
                <section class="pg-acct__pets" aria-labelledby="bk-empty">
                    <div class="pg-acct__pets-art" aria-hidden="true">
                        <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="56" fill="currentColor" opacity=".12"/><g fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"><rect x="28" y="34" width="64" height="56" rx="8"/><path d="M28 52h64M46 26v14M74 26v14"/></g><path fill="currentColor" d="M52 70l6 6 12-12" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <?php if ($temAnimais): ?>
                            <h2 id="bk-empty">Ainda não tem reservas</h2>
                            <p>Escolha as datas, o quarto de cada animal e os serviços extra. Vê o preço logo e o hotel confirma o pedido.</p>
                            <a class="pg-btn pg-btn--primary" href="<?= Url::to(['create']) ?>">Fazer a primeira reserva</a>
                        <?php else: ?>
                            <h2 id="bk-empty">Primeiro, registe o seu animal</h2>
                            <p>Para reservar uma estadia, a equipa precisa de conhecer o animal: raça, vacinas e cuidados especiais.</p>
                            <a class="pg-btn pg-btn--primary" href="<?= Url::to(['/pet/create']) ?>">Registar animal</a>
                        <?php endif; ?>
                    </div>
                </section>
            <?php else: ?>
                <?php if ($proximas !== []): ?>
                    <section class="pg-bk__section" aria-labelledby="bk-proximas">
                        <h2 class="pg-bk__heading" id="bk-proximas">Próximas e a decorrer</h2>
                        <ul class="pg-bk__list">
                            <?php foreach ($proximas as $i => $b): ?><?= $cartao($b, $i) ?><?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if ($anteriores !== []): ?>
                    <section class="pg-bk__section" aria-labelledby="bk-anteriores">
                        <h2 class="pg-bk__heading" id="bk-anteriores">Anteriores</h2>
                        <ul class="pg-bk__list pg-bk__list--past">
                            <?php foreach ($anteriores as $i => $b): ?><?= $cartao($b, $i) ?><?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
