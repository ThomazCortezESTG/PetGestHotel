<?php

declare(strict_types=1);

/**
 * Uma reserva do cliente: em que ponto está, os animais, o preço e o que pode fazer (alterar, cancelar, falar com o hotel).
 *
 * @var yii\web\View $this
 * @var common\models\Booking $model
 */

use common\models\Booking;
use common\models\Conversa;
use frontend\assets\BookingAsset;
use yii\helpers\Html;
use yii\helpers\Url;

BookingAsset::register($this);

$fmt = Yii::$app->formatter;
$linhas = $model->bookingAnimalQuartos;
$nomes = array_map(static fn ($l) => $l->animal->nome, $linhas);
$noites = $model->noites;
$resp = $model->conversa?->colaborador;
$pResp = $resp ? Conversa::pessoa($resp) : null;
$euro = static fn ($v): string => Yii::$app->formatter->asCurrency((float) $v, 'EUR');

$this->title = 'Reserva #' . $model->id . ' | PetGestHotel';

// Passos da reserva: [título, texto enquanto é o passo atual, texto depois de passar].
// Rejeitada e cancelada mostram uma mensagem no lugar dos passos.
$passos = [
    Booking::STATUS_PENDENTE => ['Pedido enviado', 'À espera de resposta do hotel', 'Recebido pelo hotel'],
    Booking::STATUS_CONFIRMADA => ['Confirmada', $pResp ? 'Acompanhada por ' . $pResp['nome'] : 'O hotel aceitou o pedido', 'Aceite pelo hotel'],
    Booking::STATUS_EM_CURSO => ['No hotel', 'Check-in feito', 'Check-in feito'],
    Booking::STATUS_CONCLUIDA => ['Terminada', 'Check-out feito', 'Check-out feito'],
];
$ordem = array_keys($passos);
$atual = array_search($model->status, $ordem, true);
$interrompida = $atual === false; // rejeitada ou cancelada

// Preço discriminado (igual ao Booking::calcularPreco)
$servicosPorAnimal = [];
foreach ($linhas as $l) {
    foreach ($l->servicos as $s) {
        $servicosPorAnimal[$s->id] = ['nome' => $s->nome, 'preco' => (float) $s->preco, 'n' => ($servicosPorAnimal[$s->id]['n'] ?? 0) + 1];
    }
}
?>

<div class="pg-acct pg-bk">
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="bk-title">
        <div class="pg-acct__wrap pg-pets__band">
            <div>
                <a class="pg-acct__back" href="<?= Url::to(['index']) ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    As minhas reservas
                </a>
                <h1 class="pg-acct__title" id="bk-title"><?= Html::encode($nomes ? 'Estadia de ' . implode(', ', $nomes) : 'Reserva #' . $model->id) ?></h1>
                <p class="pg-acct__lead">
                    <?= $fmt->asDate($model->datainicio, 'd MMMM') ?> a <?= $fmt->asDate($model->datafim, 'd MMMM yyyy') ?>
                    · <?= $noites ?> <?= $noites === 1 ? 'noite' : 'noites' ?>
                </p>
            </div>
            <?= $this->render('_estado', ['booking' => $model]) ?>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'reservas']) ?>

        <div class="pg-acct__main">
            <?php // ---------- em que ponto está ---------- ?>
            <?php if ($interrompida): ?>
                <div class="pg-bk-alert pg-bk-alert--<?= Html::encode((string) $model->status) ?>" role="status">
                    <?php if ($model->status === Booking::STATUS_REJEITADA): ?>
                        <strong>O hotel não pôde aceitar esta reserva.</strong>
                        <?php if ($model->motivo_rejeicao): ?><p><?= Html::encode($model->motivo_rejeicao) ?></p><?php endif; ?>
                        <a class="pg-btn pg-btn--primary" href="<?= Url::to(['create']) ?>">Pedir outras datas</a>
                    <?php else: ?>
                        <strong>Cancelou esta reserva.</strong>
                        <p>Se mudar de ideias, pode fazer um pedido novo.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <ol class="pg-bk-steps" aria-label="Estado da reserva">
                    <?php foreach ($passos as $estado => [$titulo, $textoAtual, $textoFeito]):
                        $i = array_search($estado, $ordem, true);
                        $texto = $i < $atual ? $textoFeito : $textoAtual;
                        $classe = $i < $atual ? 'is-done' : ($i === $atual ? 'is-current' : '');
                    ?>
                        <li class="pg-bk-steps__step <?= $classe ?>"<?= $i === $atual ? ' aria-current="step"' : '' ?>>
                            <span class="pg-bk-steps__dot" aria-hidden="true">
                                <?php if ($i < $atual || ($i === $atual && $estado === Booking::STATUS_CONCLUIDA)): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                <?php endif; ?>
                            </span>
                            <span class="pg-bk-steps__title"><?= Html::encode($titulo) ?></span>
                            <?php if ($i <= $atual): ?><span class="pg-bk-steps__text"><?= Html::encode($texto) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php // ---------- falar com o hotel ---------- ?>
            <?php if ($model->conversa !== null && !$interrompida): ?>
                <a class="pg-bk-contact" href="<?= Url::to(['/conversa/view', 'id' => $model->conversa->id]) ?>">
                    <span class="pg-bk-contact__avatar" aria-hidden="true">
                        <?php if ($pResp && $pResp['foto_url']): ?><?= Html::img($pResp['foto_url'], ['alt' => '']) ?><?php else: ?><?= Html::encode($pResp['iniciais'] ?? 'PG') ?><?php endif; ?>
                    </span>
                    <span class="pg-bk-contact__text">
                        <strong><?= $pResp ? 'Falar com ' . Html::encode($pResp['nome']) : 'Falar com a equipa' ?></strong>
                        <small><?= $model->status === Booking::STATUS_CONCLUIDA ? 'Ver as mensagens da estadia' : 'Dúvidas, horários, como está o seu animal' ?></small>
                    </span>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </a>
            <?php endif; ?>

            <?php // ---------- animais ---------- ?>
            <div class="pg-acct__group" role="group" aria-labelledby="h-animais">
                <div class="pg-acct__group-head"><h2 id="h-animais">Animais</h2><p>Quem fica no hotel e em que quarto.</p></div>
                <ul class="pg-bk-guests">
                    <?php foreach ($linhas as $l): $a = $l->animal; ?>
                        <li class="pg-bk-guest">
                            <a href="<?= Url::to(['/pet/view', 'id' => $a->id]) ?>" class="pg-bk-guest__medal" tabindex="-1" aria-hidden="true">
                                <?= $this->render('@frontend/views/pet/_medal', ['nome' => $a->nome, 'especie' => $a->getEspecie(), 'foto' => $a->getFotoUrl(), 'size' => 'sm']) ?>
                            </a>
                            <div>
                                <a class="pg-bk-guest__name" href="<?= Url::to(['/pet/view', 'id' => $a->id]) ?>"><?= Html::encode($a->nome) ?></a>
                                <p class="pg-bk-guest__room">Quarto <?= Html::encode(ucfirst((string) ($l->quarto->categoria ?? ''))) ?></p>
                                <?php if ($l->servicos): ?>
                                    <p class="pg-bk-guest__extras"><?= Html::encode(implode(' · ', array_map(static fn ($s) => $s->nome, $l->servicos))) ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php // ---------- preço ---------- ?>
            <div class="pg-acct__group" role="group" aria-labelledby="h-preco">
                <div class="pg-acct__group-head"><h2 id="h-preco">Preço</h2><p>Quartos pelas noites da estadia, mais os serviços extra de cada animal.</p></div>
                <div class="pg-bk-bill">
                    <ul>
                        <?php foreach ($linhas as $l): ?>
                            <li>
                                <span><?= Html::encode($l->animal->nome) ?> · Quarto <?= Html::encode(ucfirst((string) ($l->quarto->categoria ?? ''))) ?> <small><?= $euro($l->quarto->preco ?? 0) ?> × <?= $noites ?> <?= $noites === 1 ? 'noite' : 'noites' ?></small></span>
                                <span><?= $euro(($l->quarto->preco ?? 0) * $noites) ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php foreach ($servicosPorAnimal as $s): ?>
                            <li class="pg-bk-bill__extra">
                                <span><?= Html::encode($s['nome']) ?><?php if ($s['n'] > 1): ?> <small><?= $euro($s['preco']) ?> × <?= $s['n'] ?> animais</small><?php endif; ?></span>
                                <span><?= $euro($s['preco'] * $s['n']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="pg-bk-bill__total"><span>Total</span><strong><?= $euro($model->preco ?? 0) ?></strong></p>
                </div>
            </div>

            <?php // ---------- ações do cliente ---------- ?>
            <?php if ($model->podeSerEditadaPeloCliente() || $model->podeSerCanceladaPeloCliente()): ?>
                <div class="pg-acct__group" role="group" aria-labelledby="h-acoes">
                    <div class="pg-acct__group-head">
                        <h2 id="h-acoes">Alterar ou cancelar</h2>
                        <p><?= $model->podeSerEditadaPeloCliente()
                            ? 'Pode alterar o pedido enquanto o hotel não responde.'
                            : 'Já não pode alterar a reserva, mas pode cancelá-la até ao dia da entrada.' ?></p>
                    </div>
                    <div class="pg-bk-actions">
                        <?php if ($model->podeSerEditadaPeloCliente()): ?>
                            <a class="pg-btn pg-btn--outline" href="<?= Url::to(['update', 'id' => $model->id]) ?>">Alterar pedido</a>
                        <?php endif; ?>
                        <?= Html::beginForm(['cancelar', 'id' => $model->id], 'post', ['class' => 'pg-bk-actions__cancel']) ?>
                            <?= Html::submitButton('Cancelar reserva', [
                                'class' => 'pg-btn pg-bk-btn-danger',
                                'data-confirm' => 'Cancelar esta reserva? Não é possível voltar atrás.',
                            ]) ?>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
