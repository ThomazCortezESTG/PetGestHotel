<?php

declare(strict_types=1);

/**
 * Página "Sobre nós".
 * Usa o layout "landing" (definido no SiteController::actionAbout).
 *
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Sobre nós | PetGestHotel';
$this->params['meta_description'] = 'O PetGestHotel é um hotel para cães e gatos em Leiria, com reservas online e chat com a equipa durante a estadia.';

$hotel = Yii::$app->params['hotel'];
$mapa = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($hotel['morada'] . ', ' . $hotel['codigoPostal']);

// Ícones SVG (decorativos: aria-hidden)
$svg = static fn(string $paths): string =>
    '<svg class="pg-icon" viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';

$principios = [
    [
        'icone' => $svg('<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>'),
        'titulo' => 'Cada animal mantém a sua rotina',
        'texto' => 'Horas de refeição, passeios e medicação seguem a ficha que preencheu. Não tem de explicar tudo outra vez à porta.',
    ],
    [
        'icone' => $svg('<path d="M4 20V9l8-5 8 5v11"/><path d="M12 20v-7"/><path d="M8 20v-4M16 20v-4"/>'),
        'titulo' => 'Gatos de um lado, cães do outro',
        'texto' => 'Os gatos ficam numa ala própria, sem ladridos, com arranhadores, prateleiras e sítios para se esconderem.',
    ],
    [
        'icone' => $svg('<path d="M4.5 6.5A2.5 2.5 0 0 1 7 4h10a2.5 2.5 0 0 1 2.5 2.5v7A2.5 2.5 0 0 1 17 16h-6l-4.5 3.5V16H7a2.5 2.5 0 0 1-2.5-2.5z"/>'),
        'titulo' => 'Sabe sempre como ele está',
        'texto' => 'Pergunte no chat a qualquer hora. A equipa responde com mensagens, fotos e vídeos do dia.',
    ],
];

$instalacoes = [
    'Quartos para cães' => '18, com cama elevada e janela para o pátio',
    'Suítes para gatos' => '10, numa ala separada e silenciosa',
    'Pátio exterior' => '600 m² vedados, com relva e sombra',
    'Equipa' => '6 cuidadores e uma veterinária parceira a 5 minutos',
];
?>

<!-- ============ INTRODUÇÃO ============ -->
<section class="pg-section pg-section--tint pg-about-intro" aria-labelledby="sobre-title">
    <div class="pg-container pg-about-intro__inner">
        <div class="pg-about-intro__text" data-reveal-group>
            <h1 class="pg-title pg-title--page" id="sobre-title" data-reveal>Um hotel pequeno, para cães e gatos, em Leiria.</h1>
            <p class="pg-lead" data-reveal>Abrimos o PetGestHotel porque deixar um animal ao cuidado de outra pessoa custa menos quando se sabe o que ele está a fazer. Por isso cada reserva tem chat com a equipa, e cada estadia tem fotos.</p>
            <p class="pg-lead" data-reveal>Recebemos poucos animais de cada vez, para que todos tenham atenção e ninguém passe o dia fechado.</p>
        </div>
        <figure class="pg-about-intro__media" data-reveal="clip">
            <?= Html::img('@web/images/sobrenos.jpg', ['alt' => 'Equipa do PetGestHotel sentada na relva do pátio com três cães']) ?>
        </figure>
    </div>
</section>

<!-- ============ PRINCÍPIOS ============ -->
<section class="pg-section" aria-labelledby="principios-title">
    <div class="pg-container">
        <div class="pg-head" data-reveal>
            <h2 class="pg-title" id="principios-title">Como cuidamos deles</h2>
        </div>
        <ul class="pg-principles" data-reveal-group>
            <?php foreach ($principios as $p): ?>
                <li class="pg-principle" data-reveal>
                    <?= $p['icone'] ?>
                    <h3><?= Html::encode($p['titulo']) ?></h3>
                    <p><?= Html::encode($p['texto']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- ============ INSTALAÇÕES ============ -->
<section class="pg-section pg-section--flush" aria-labelledby="instalacoes-title">
    <div class="pg-container pg-facilities">
        <div class="pg-facilities__head" data-reveal>
            <h2 class="pg-title" id="instalacoes-title">As instalações</h2>
            <p class="pg-lead">Pode visitar o hotel antes da primeira reserva. Basta marcar pelo telefone ou por email.</p>
        </div>
        <dl class="pg-facilities__list" data-reveal-group>
            <?php foreach ($instalacoes as $nome => $detalhe): ?>
                <div data-reveal="line">
                    <dt><?= Html::encode($nome) ?></dt>
                    <dd><?= Html::encode($detalhe) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>

<!-- ============ CONTACTOS ============ -->
<section class="pg-section pg-section--tint" id="contactos" aria-labelledby="contactos-title">
    <div class="pg-container pg-visit">
        <div class="pg-visit__col" data-reveal-group>
            <h2 class="pg-title" id="contactos-title" data-reveal>Onde estamos</h2>
            <address class="pg-visit__address" data-reveal>
                <?= Html::encode($hotel['morada']) ?><br>
                <?= Html::encode($hotel['codigoPostal']) ?>
            </address>
            <ul class="pg-visit__contacts" data-reveal>
                <li><a href="tel:<?= Html::encode(str_replace(' ', '', $hotel['telefone'])) ?>"><?= Html::encode($hotel['telefone']) ?></a></li>
                <li><a href="mailto:<?= Html::encode($hotel['email']) ?>"><?= Html::encode($hotel['email']) ?></a></li>
            </ul>
            <div class="pg-visit__actions" data-reveal>
                <a class="pg-btn pg-btn--primary" href="<?= Html::encode($mapa) ?>" target="_blank" rel="noopener">Abrir no Google Maps</a>
                <a class="pg-btn pg-btn--outline" href="<?= Url::to(['/site/contact']) ?>">Enviar mensagem</a>
            </div>
        </div>

        <div class="pg-visit__col pg-visit__hours" data-reveal>
            <h3>Entradas e saídas</h3>
            <dl>
                <?php foreach ($hotel['horario'] as $dias => $horas): ?>
                    <div>
                        <dt><?= Html::encode($dias) ?></dt>
                        <dd><?= Html::encode($horas) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <p>A equipa está no hotel 24 horas por dia. Fora deste horário, os animais estão acompanhados, mas não há entradas nem saídas.</p>
        </div>
    </div>
</section>

<!-- ============ CTA FINAL ============ -->
<section class="pg-cta" aria-labelledby="cta-title">
    <div class="pg-container pg-cta__inner" data-reveal="zoom">
        <div class="pg-cta__text">
            <h2 class="pg-title" id="cta-title">Reserve as próximas férias do seu animal</h2>
            <p class="pg-lead">Crie a conta, adicione o seu animal e escolha as datas.</p>
        </div>
        <div class="pg-cta__actions">
            <a class="pg-btn pg-btn--primary" href="<?= Url::to(['/site/signup']) ?>">Criar conta</a>
            <a class="pg-btn pg-btn--light" href="<?= Url::to(['/site/index', '#' => 'como-funciona']) ?>">Ver como funciona</a>
        </div>
    </div>
</section>
