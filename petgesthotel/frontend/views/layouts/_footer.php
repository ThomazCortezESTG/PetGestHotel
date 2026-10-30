<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$hotel = Yii::$app->params['hotel'];
$mapa = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($hotel['morada'] . ', ' . $hotel['codigoPostal']);
?>
<footer class="pg-footer">
    <div class="pg-container">
        <div class="pg-footer__top">
            <div class="pg-footer__brand">
                <?php /* Texto em vez do logo: o símbolo e o "Pet" são azul-escuro e não se leem sobre o fundo */ ?>
                <a class="pg-footer__name" href="<?= Url::home() ?>" aria-label="PetGestHotel, ir para o início">
                    Pet<span class="pg-footer__name-gest">Gest</span><span class="pg-footer__name-hotel">Hotel</span>
                </a>
                <p>Hotel para cães e gatos em Leiria. Reservas, chat e acompanhamento do seu animal num só lugar.</p>
            </div>

            <div class="pg-footer__cols">
                <div class="pg-footer__col">
                    <h2>Hotel</h2>
                    <a href="<?= Url::to(['/site/index', '#' => 'servicos']) ?>">Serviços</a>
                    <a href="<?= Url::to(['/site/index', '#' => 'como-funciona']) ?>">Como funciona</a>
                    <a href="<?= Url::to(['/site/index', '#' => 'app']) ?>">A app</a>
                    <a href="<?= Url::to(['/site/about']) ?>">Sobre nós</a>
                </div>
                <div class="pg-footer__col">
                    <h2>Conta</h2>
                    <a href="<?= Url::to(['/site/login']) ?>">Entrar</a>
                    <a href="<?= Url::to(['/site/signup']) ?>">Criar conta</a>
                </div>
                <div class="pg-footer__col">
                    <h2>Contactos</h2>
                    <a href="mailto:<?= Html::encode($hotel['email']) ?>"><?= Html::encode($hotel['email']) ?></a>
                    <a href="tel:<?= Html::encode(str_replace(' ', '', $hotel['telefone'])) ?>"><?= Html::encode($hotel['telefone']) ?></a>
                    <a href="<?= Html::encode($mapa) ?>" target="_blank" rel="noopener">
                        <?= Html::encode($hotel['morada']) ?><br><?= Html::encode($hotel['codigoPostal']) ?>
                    </a>
                    <a href="<?= Url::to(['/site/contact']) ?>">Enviar mensagem</a>
                </div>
            </div>
        </div>

        <div class="pg-footer__bottom">© <?= date('Y') ?> PetGestHotel</div>
    </div>
</footer>
