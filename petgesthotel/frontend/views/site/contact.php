<?php

declare(strict_types=1);

/**
 * Página "Contacte-nos".
 * Usa o layout "landing" (definido no SiteController::actionContact).
 *
 * @var yii\web\View $this
 * @var yii\bootstrap5\ActiveForm $form
 * @var \frontend\models\ContactForm $model
 */

use frontend\models\ContactForm;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;
use yii\helpers\Url;

$this->title = 'Contacte-nos | PetGestHotel';
$this->params['meta_description'] = 'Fale com o PetGestHotel: telefone, email, morada em Leiria e formulário de contacto.';

$hotel = Yii::$app->params['hotel'];
$mapa = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($hotel['morada'] . ', ' . $hotel['codigoPostal']);

// Ícones SVG (decorativos: aria-hidden)
$svg = static fn(string $paths): string =>
    '<svg class="pg-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';

$canais = [
    [
        'icone' => $svg('<path d="M5 4h3.5l1.8 4.5-2.3 1.4a11 11 0 0 0 6.1 6.1l1.4-2.3L20 15.5V19a1.5 1.5 0 0 1-1.6 1.5A16.5 16.5 0 0 1 3.5 5.6 1.5 1.5 0 0 1 5 4z"/>'),
        'titulo' => 'Telefone',
        'valor' => $hotel['telefone'],
        'href' => 'tel:' . str_replace(' ', '', $hotel['telefone']),
        'nota' => 'A forma mais rápida para marcar uma visita.',
    ],
    [
        'icone' => $svg('<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 7l8.5 6 8.5-6"/>'),
        'titulo' => 'Email',
        'valor' => $hotel['email'],
        'href' => 'mailto:' . $hotel['email'],
        'nota' => 'Para dúvidas, orçamentos e documentos.',
    ],
    [
        'icone' => $svg('<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>'),
        'titulo' => 'Morada',
        'valor' => $hotel['morada'] . ', ' . $hotel['codigoPostal'],
        'href' => $mapa,
        'nota' => 'Abrir no Google Maps.',
        'externo' => true,
    ],
];

$campo = ['options' => ['class' => 'pg-contact__field']];
?>

<section class="pg-section pg-section--tint pg-contact" aria-labelledby="contacto-title">
    <div class="pg-container">
        <div class="pg-contact__head" data-reveal-group>
            <h1 class="pg-title pg-title--page" id="contacto-title" data-reveal>Fale connosco</h1>
            <p class="pg-lead" data-reveal>Dúvidas sobre reservas, uma visita ao hotel ou lugar nas suas datas: ligue-nos ou envie uma mensagem. Respondemos por email <?= Html::encode($hotel['tempoResposta']) ?>.</p>
        </div>

        <div class="pg-contact__grid">
            <!-- Contactos diretos: no telemóvel ficam antes do formulário -->
            <ul class="pg-contact__channels" aria-label="Contactos diretos">
                <?php foreach ($canais as $c): ?>
                    <li>
                        <span class="pg-contact__channel-icon"><?= $c['icone'] ?></span>
                        <div>
                            <h2><?= Html::encode($c['titulo']) ?></h2>
                            <a href="<?= Html::encode($c['href']) ?>"<?= !empty($c['externo']) ? ' target="_blank" rel="noopener"' : '' ?>><?= Html::encode($c['valor']) ?></a>
                            <p><?= Html::encode($c['nota']) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Formulário -->
            <div class="pg-contact__card">
                <h2 class="pg-contact__card-title">Enviar mensagem</h2>

                <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>

                <?= $form->field($model, 'name', $campo)->textInput(['autocomplete' => 'name']) ?>

                <?= $form->field($model, 'email', $campo)
                    ->input('email', ['autocomplete' => 'email'])
                    ->hint('É para aqui que enviamos a resposta.') ?>

                <?= $form->field($model, 'subject', $campo)
                    ->dropDownList(ContactForm::ASSUNTOS, ['prompt' => 'Escolha um assunto']) ?>

                <?= $form->field($model, 'body', $campo)
                    ->textarea(['rows' => 6])
                    ->hint('Se for sobre uma reserva, indique as datas e o nome e a espécie do animal.') ?>

                <?= $form->field($model, 'verifyCode', $campo)->widget(Captcha::class, [
                    'template' => '<div class="pg-contact__captcha">{image}{input}</div>',
                    'imageOptions' => [
                        'class' => 'pg-contact__captcha-img',
                        'alt' => 'Código de verificação. Clique para gerar outro.',
                        'title' => 'Clique para gerar outro código',
                    ],
                    'options' => ['class' => 'form-control', 'autocomplete' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false'],
                ])->hint('Escreva as letras da imagem. Não consegue ler? Clique nela para gerar outras.') ?>

                <div class="pg-contact__submit">
                    <?= Html::submitButton('Enviar mensagem', [
                        'class' => 'pg-btn pg-btn--primary',
                        'name' => 'contact-button',
                    ]) ?>
                    <p>Usamos os seus dados só para lhe responder.</p>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <!-- Horário -->
            <div class="pg-visit__hours pg-contact__hours">
                <h2>Entradas e saídas</h2>
                <dl>
                    <?php foreach ($hotel['horario'] as $dias => $horas): ?>
                        <div>
                            <dt><?= Html::encode($dias) ?></dt>
                            <dd><?= Html::encode($horas) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
                <p>Já é cliente? Durante a estadia, o chat da reserva é a forma mais rápida de falar com a equipa.</p>
                <p>Talvez a resposta já esteja nas <a href="<?= Url::to(['/site/index', '#' => 'perguntas']) ?>">perguntas frequentes</a>.</p>
            </div>
        </div>
    </div>
</section>
