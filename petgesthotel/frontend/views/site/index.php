<?php

declare(strict_types=1);

/**
 * Landing page do visitante.
 * Usa o layout "landing" (definido no SiteController::actionIndex).
 *
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'PetGestHotel | Hotel para animais';

// Ícones SVG reutilizados (decorativos: aria-hidden)
$svg = static fn(string $paths, int $size = 28): string =>
    '<svg class="pg-icon" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';

$iconCalendar = $svg('<rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4"/>');
$iconCamera   = $svg('<path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.2l1.3-2h6l1.3 2h1.2A2.5 2.5 0 0 1 20 8.5v8A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5z"/><circle cx="12" cy="12.5" r="3.3"/>');
$iconChat     = $svg('<path d="M4.5 6.5A2.5 2.5 0 0 1 7 4h10a2.5 2.5 0 0 1 2.5 2.5v7A2.5 2.5 0 0 1 17 16h-6l-4.5 3.5V16H7a2.5 2.5 0 0 1-2.5-2.5z"/>');
$iconUser     = $svg('<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.6-3.6 3.4-5.5 7-5.5s6.4 1.9 7 5.5"/>');
$iconPlus     = $svg('<path d="M12 5v14M5 12h14"/>', 24);
$iconSend     = $svg('<path d="M5 12h13M13 6l6 6-6 6"/>', 20);
$paw = '<svg viewBox="0 0 32 32" width="18" height="18" fill="#FFFFFF" aria-hidden="true"><ellipse cx="16" cy="21" rx="7" ry="5.5"/><circle cx="7.5" cy="14" r="2.8"/><circle cx="13" cy="9" r="2.8"/><circle cx="19" cy="9" r="2.8"/><circle cx="24.5" cy="14" r="2.8"/></svg>';

$faq = [
    'Aceitam cães e gatos?' =>
        'Sim, os dois. Os gatos ficam numa zona própria, sossegada e separada dos cães, com arranhadores e esconderijos.',
    'Preciso de ter conta para reservar?' =>
        'Sim. Criar conta é rápido: regista os seus dados, adiciona os seus animais e só depois escolhe as datas.',
    'Posso cancelar uma reserva?' =>
        'Pode cancelar na página da reserva, no site ou na app. Sem custos até 48 horas antes da data de entrada.',
    'Como sei como está o meu animal?' =>
        'Pergunte no chat e a equipa responde com mensagens, fotos e vídeos. O histórico recente fica sempre na sua conta.',
    'Posso registar mais do que um animal?' =>
        'Sim. Cada animal tem o seu perfil, que pode editar sempre que for preciso.',
];

// Serviços: a foto de cada um fica em web/images/servicos/ (quadrada, 1:1).
// Enquanto o ficheiro não existir, aparece um fundo com o nome do ficheiro esperado.
$servicos = [
    'alojamento' => [
        'titulo' => 'Alojamento',
        'texto' => 'Quartos tranquilos e limpos, com uma zona só para gatos, longe do barulho dos cães.',
        'foto' => 'alojamento.jpg',
        'alt' => 'Cão borzoi deitado numa cama grande num quarto do hotel',
    ],
    'creche' => [
        'titulo' => 'Creche de dia',
        'texto' => 'Para os dias em que precisa de sair e não quer deixá-lo sozinho em casa.',
        'foto' => 'creche.jpg',
        'alt' => 'Cuidadora a brincar com gatos na sala de dia, com prateleiras e túneis',
    ],
    'passeios' => [
        'titulo' => 'Brincadeira e passeios',
        'texto' => 'Passeios ao ar livre para os cães, brinquedos e arranhadores para os gatos.',
        'foto' => 'brincadeira.jpg',
        'alt' => 'Cuidador a passear um golden retriever num caminho entre pinheiros',
    ],
    'cuidados' => [
        'titulo' => 'Cuidados especiais',
        'texto' => 'Medicação e dietas indicadas na ficha do animal, sem ter de repetir nada.',
        'foto' => 'cuidados.jpg',
        'alt' => 'Cuidadora a acompanhar a refeição de um cão sénior, com a ficha na mão',
    ],
];
?>

<!-- ============ HERO ============ -->
<section class="pg-hero" aria-labelledby="hero-title" data-reveal-group>
    <?= Html::img('@web/images/caes.gif', ['class' => 'pg-hero__media', 'alt' => '', 'aria-hidden' => 'true']) ?>
    <div class="pg-hero__shade" aria-hidden="true"></div>

    <div class="pg-container pg-hero__content">
        <h1 class="pg-hero__title" id="hero-title" data-reveal>Férias descansadas para si e para o seu animal.</h1>
        <p class="pg-hero__text" data-reveal>Reserve online, acompanhe o dia dele pela app e receba fotos e vídeos sempre que a equipa tiver novidades.</p>

        <form class="pg-booking" data-reveal method="get" action="<?= Url::to(['/reserva/create']) ?>">
            <div class="pg-field">
                <label for="pg-entrada">Entrada</label>
                <input id="pg-entrada" name="entrada" type="date" required>
            </div>
            <div class="pg-field">
                <label for="pg-saida">Saída</label>
                <input id="pg-saida" name="saida" type="date" required>
            </div>
            <div class="pg-field pg-field--small">
                <label for="pg-animal">Animal</label>
                <select id="pg-animal" name="tipo">
                    <option value="cao">Cão</option>
                    <option value="gato">Gato</option>
                </select>
            </div>
            <button type="submit" class="pg-btn pg-btn--primary">Ver disponibilidade</button>
        </form>
    </div>
</section>

<!-- ============ VANTAGENS ============ -->
<section class="pg-perks" aria-label="Vantagens">
    <ul class="pg-container pg-perks__list" data-reveal-group>
        <li data-reveal><?= $iconCalendar ?>Reserva online, a qualquer hora</li>
        <li data-reveal><?= $iconCamera ?>Fotos e vídeos do dia a dia</li>
        <li data-reveal><?= $iconChat ?>Chat direto com a equipa</li>
    </ul>
</section>

<!-- ============ COMO FUNCIONA ============ -->
<section class="pg-section" id="como-funciona" aria-labelledby="como-title">
    <div class="pg-container">
        <div class="pg-head" data-reveal>
            <h2 class="pg-title" id="como-title">Do registo à recolha, tudo no mesmo sítio</h2>
            <p class="pg-lead">Quatro passos simples, no site ou na app.</p>
        </div>
        <ol class="pg-steps" data-reveal-group>
            <li class="pg-step" data-reveal="line">
                <span class="pg-step__num" aria-hidden="true">1</span>
                <h3>Crie a sua conta</h3>
                <p>Registe os seus dados e adicione cada animal com a respetiva ficha.</p>
            </li>
            <li class="pg-step" data-reveal="line">
                <span class="pg-step__num" aria-hidden="true">2</span>
                <h3>Escolha as datas</h3>
                <p>Indique entrada, saída e o animal. Confirme depois de ver o resumo.</p>
            </li>
            <li class="pg-step" data-reveal="line">
                <span class="pg-step__num" aria-hidden="true">3</span>
                <h3>Acompanhe à distância</h3>
                <p>Fale com a equipa no chat e receba fotos e vídeos durante a estadia.</p>
            </li>
            <li class="pg-step" data-reveal="line">
                <span class="pg-step__num" aria-hidden="true">4</span>
                <h3>Recolha e reveja</h3>
                <p>Consulte o histórico recente de cada animal na sua conta.</p>
            </li>
        </ol>
    </div>
</section>

<!-- ============ SERVIÇOS ============ -->
<section class="pg-section pg-section--flush" id="servicos" aria-labelledby="servicos-title">
    <div class="pg-container pg-services">
        <div class="pg-services__media" data-reveal="clip">
            <?php $primeiro = true; foreach ($servicos as $id => $servico): ?>
                <figure class="pg-services__img<?= $primeiro ? ' is-active' : '' ?>" data-service="<?= $id ?>">
                    <?php if (is_file(Yii::getAlias('@webroot/images/servicos/' . $servico['foto']))): ?>
                        <?= Html::img('@web/images/servicos/' . $servico['foto'], ['alt' => $servico['alt'], 'loading' => 'lazy']) ?>
                    <?php else: ?>
                        <span class="pg-services__placeholder">images/servicos/<?= Html::encode($servico['foto']) ?></span>
                    <?php endif; ?>
                </figure>
            <?php $primeiro = false; endforeach; ?>
        </div>

        <div class="pg-services__list">
            <h2 class="pg-title" id="servicos-title" data-reveal>O que o seu animal encontra cá</h2>
            <div data-reveal-group>
                <?php $primeiro = true; foreach ($servicos as $id => $servico): ?>
                    <button type="button" class="pg-service" data-service="<?= $id ?>" aria-pressed="<?= $primeiro ? 'true' : 'false' ?>" data-reveal>
                        <span class="pg-service__title"><?= Html::encode($servico['titulo']) ?></span>
                        <span class="pg-service__text"><?= Html::encode($servico['texto']) ?></span>
                    </button>
                <?php $primeiro = false; endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============ APP ============ -->
<section class="pg-section pg-section--tint" id="app" aria-labelledby="app-title">
    <div class="pg-container pg-app">
        <div class="pg-app__text" data-reveal-group>
            <h2 class="pg-title" id="app-title" data-reveal>Pergunte pelo seu animal. A equipa responde.</h2>
            <p class="pg-lead" data-reveal>Na app tem a reserva, o histórico e o chat com os colaboradores, com mensagens, fotos e vídeos da estadia.</p>
            <ul class="pg-app__features" data-reveal>
                <li><?= $iconCalendar ?><span><strong>Reserva à mão.</strong> Veja os dados e cancele quando precisar.</span></li>
                <li><?= $iconCamera ?><span><strong>Fotos e vídeos.</strong> A equipa partilha momentos do dia no chat.</span></li>
                <li><?= $iconUser ?><span><strong>Perfis dos animais.</strong> Edite os dados de cada um quando quiser.</span></li>
            </ul>
        </div>

        <div class="pg-phone" data-reveal="phone" role="img" aria-label="Exemplo do chat na app: o cliente pergunta como está a Luna e a equipa responde com fotos">
            <div class="pg-phone__screen" aria-hidden="true">
                <div class="pg-phone__bar">
                    <span class="pg-phone__avatar"><?= $paw ?></span>
                    Equipa PetGestHotel
                </div>
                <div class="pg-phone__chat">
                    <div class="pg-msg pg-msg--me">Olá! Como está a Luna?</div>
                    <div class="pg-msg pg-msg--team">Comeu tudo e já foi brincar no pátio. Deixamos aqui umas fotos.</div>
                    <div class="pg-msg pg-msg__photos">
                        <?= Html::img('@web/images/app/caomensagem1.jpg', ['alt' => '', 'width' => 96, 'height' => 96, 'loading' => 'lazy']) ?>
                        <?= Html::img('@web/images/app/caomensagem2.jpg', ['alt' => '', 'width' => 96, 'height' => 96, 'loading' => 'lazy']) ?>
                    </div>
                    <div class="pg-msg pg-msg--me">Obrigada, que alegria!</div>
                </div>
                <div class="pg-phone__input">
                    <span>Escreva uma mensagem</span>
                    <span class="pg-phone__send"><?= $iconSend ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ PERGUNTAS ============ -->
<section class="pg-section" id="perguntas" aria-labelledby="faq-title">
    <div class="pg-container pg-faq">
        <div class="pg-faq__head" data-reveal>
            <h2 class="pg-title" id="faq-title">Perguntas frequentes</h2>
            <p class="pg-lead">Não encontrou o que procura? Fale connosco pelo chat depois de criar conta.</p>
        </div>
        <div class="pg-faq__list" data-reveal-group>
            <?php foreach ($faq as $pergunta => $resposta): ?>
                <details class="pg-faq__item" data-reveal>
                    <summary><?= Html::encode($pergunta) ?><?= $iconPlus ?></summary>
                    <p><?= Html::encode($resposta) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ CTA FINAL (só para visitantes) ============ -->
<?php if (Yii::$app->user->isGuest): ?>
<section class="pg-cta" aria-labelledby="cta-title">
    <div class="pg-container pg-cta__inner" data-reveal="zoom">
        <div class="pg-cta__text">
            <h2 class="pg-title" id="cta-title">Reserve as próximas férias do seu animal</h2>
            <p class="pg-lead">Crie a conta, adicione o seu animal e escolha as datas.</p>
        </div>
        <div class="pg-cta__actions">
            <a class="pg-btn pg-btn--primary" href="<?= Url::to(['/site/signup']) ?>">Criar conta</a>
            <a class="pg-btn pg-btn--light" href="<?= Url::to(['/site/login']) ?>">Já tenho conta</a>
        </div>
    </div>
</section>
<?php endif; ?>
