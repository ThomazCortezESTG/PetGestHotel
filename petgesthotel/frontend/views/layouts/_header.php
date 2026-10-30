<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

// Na landing as âncoras são locais (#servicos); nas outras páginas voltam à landing.
$isHome = Yii::$app->controller->route === 'site/index';
$anchor = static fn(string $id): string => $isHome ? '#' . $id : Url::to(['/site/index', '#' => $id]);
?>
<header class="pg-header" id="pg-header">
    <div class="pg-container pg-header__inner">
        <a class="pg-logo" href="<?= Url::home() ?>">
            <?= Html::img('@web/images/petGuestHotelLogo.png', [
                'alt' => 'PetGestHotel, ir para o início',
                'class' => 'pg-logo__img',
            ]) ?>
        </a>

        <button class="pg-nav-toggle" type="button" aria-expanded="false" aria-controls="pg-nav" aria-label="Abrir menu">
            <span></span><span></span><span></span>
        </button>

        <nav class="pg-nav" id="pg-nav" aria-label="Principal">
            <a href="<?= $anchor('servicos') ?>">Serviços</a>
            <a href="<?= $anchor('como-funciona') ?>">Como funciona</a>
            <a href="<?= $anchor('app') ?>">A app</a>
            <a href="<?= $anchor('perguntas') ?>">Perguntas</a>
            <a href="<?= Url::to(['/site/about']) ?>"<?= Yii::$app->controller->route === 'site/about' ? ' aria-current="page"' : '' ?>>Sobre nós</a>

            <div class="pg-nav__actions">
                <?php if (Yii::$app->user->isGuest): ?>
                    <a class="pg-btn pg-btn--outline" href="<?= Url::to(['/site/login']) ?>">Entrar</a>
                    <a class="pg-btn pg-btn--primary" href="<?= Url::to(['/site/signup']) ?>">Criar conta</a>
                <?php else: ?>
                    <a class="pg-btn pg-btn--outline" href="<?= Url::to(['/reserva/index']) ?>">As minhas reservas</a>
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'pg-nav__logout']) ?>
                        <?= Html::submitButton('Sair', ['class' => 'pg-btn pg-btn--primary']) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
