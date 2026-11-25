<?php

declare(strict_types=1);

/**
 * Lista das conversas do cliente (uma por reserva confirmada).
 *
 * @var yii\web\View $this
 * @var common\models\Conversa[] $conversas
 */

use common\models\Conversa;
use frontend\assets\ConversaAsset;
use yii\helpers\Html;
use yii\helpers\Url;

ConversaAsset::register($this);

$this->title = 'Mensagens | PetGestHotel';
$eu = (int) Yii::$app->user->id;
$fmt = Yii::$app->formatter;
?>

<div class="pg-acct pg-msgs">
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="msgs-title">
        <div class="pg-acct__wrap">
            <h1 class="pg-acct__title" id="msgs-title">Mensagens</h1>
            <p class="pg-acct__lead">Fale com a pessoa da equipa que acompanha a estadia do seu animal.</p>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'mensagens']) ?>

        <div class="pg-acct__main">
            <?php if ($conversas === []): ?>
                <section class="pg-acct__pets" aria-labelledby="msgs-empty">
                    <div class="pg-acct__pets-art" aria-hidden="true">
                        <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="56" fill="currentColor" opacity=".12"/><path fill="currentColor" d="M30 38h60a8 8 0 0 1 8 8v30a8 8 0 0 1-8 8H56l-16 12v-12H30a8 8 0 0 1-8-8V46a8 8 0 0 1 8-8z"/></svg>
                    </div>
                    <div>
                        <h2 id="msgs-empty">Ainda não tem conversas</h2>
                        <p>Quando o hotel confirmar uma reserva sua, fica aqui uma conversa com o colaborador que vai acompanhar a estadia. Pode tirar dúvidas antes da entrada e saber como está o seu animal durante a estadia.</p>
                    </div>
                </section>
            <?php else: ?>
                <ul class="pg-msgs__list">
                    <?php foreach ($conversas as $c):
                        $d = $c->dados($eu);
                        $nomeAnimais = implode(', ', array_column($d['reserva']['animais'], 'nome')) ?: 'Reserva #' . $c->id_booking;
                        $quem = $d['responsavel']['nome'] ?? 'À espera de um colaborador';
                        $ultima = $d['ultima_mensagem'];
                    ?>
                        <li>
                            <a class="pg-msgs__item<?= $d['nao_lidas'] > 0 ? ' pg-msgs__item--unread' : '' ?>" href="<?= Url::to(['view', 'id' => $c->id]) ?>">
                                <?= $this->render('_responsavel', ['conversa' => $c, 'class' => 'pg-msgs__avatar']) ?>
                                <span class="pg-msgs__body">
                                    <span class="pg-msgs__top">
                                        <strong><?= Html::encode($nomeAnimais) ?></strong>
                                        <?php if ($ultima): ?>
                                            <time datetime="<?= Html::encode($ultima['created_at']) ?>"><?= $fmt->asRelativeTime(strtotime($ultima['created_at'])) ?></time>
                                        <?php endif; ?>
                                    </span>
                                    <span class="pg-msgs__meta">
                                        <?= Html::encode($quem) ?> ·
                                        <?= $fmt->asDate($c->booking->datainicio, 'd MMM') ?> a <?= $fmt->asDate($c->booking->datafim, 'd MMM') ?> ·
                                        <?= Html::encode($d['reserva']['estado_texto']) ?>
                                    </span>
                                    <?php if ($ultima): ?>
                                        <span class="pg-msgs__last"><?= $ultima['minha'] ? 'Eu: ' : '' ?><?= Html::encode(mb_strimwidth($ultima['texto'], 0, 110, '…')) ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($d['nao_lidas'] > 0): ?>
                                    <span class="pg-msgs__badge"><?= $d['nao_lidas'] ?><span class="visually-hidden"> mensagens por ler</span></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
