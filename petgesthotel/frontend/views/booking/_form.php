<?php

declare(strict_types=1);

/**
 * Formulário da reserva (pedir e alterar), no estilo do formulário dos animais: grupos com título à
 * esquerda e, ao lado, o resumo com o preço, que se atualiza enquanto se escolhe (web/js/booking.js).
 *
 * Cada animal tem o seu quarto, os seus serviços extra e, se quiser, datas próprias dentro das da reserva.
 * Envia: Booking[datainicio], Booking[datafim] e, por animal,
 *   quartos[id] = id_quarto (vazio = não vem), servicos[id][] = id_servico,
 *   datas[id][proprias] = 1, datas[id][inicio], datas[id][fim].
 *
 * @var yii\web\View $this
 * @var common\models\Booking $model
 * @var common\models\Quarto[] $quartos
 * @var common\models\Animal[] $animais
 * @var common\models\ServicosExtras[] $servicos
 * @var array $escolhas [id_animal => ['quarto', 'servicos', 'proprias', 'inicio', 'fim']]
 */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$isNew = $model->isNewRecord;
$hoje = date('Y-m-d');
if ($isNew && empty($model->datainicio)) {
    $model->datainicio = date('Y-m-d', strtotime('+1 day'));
}
$fmtPreco = static fn ($v): string => number_format((float) $v, 2, ',', ' ') . ' €';
// se só há um animal, já vem escolhido (no quarto mais barato)
$soUmAnimal = count($animais) === 1 && $escolhas === [] && $isNew;
?>

<?php if ($animais === []): ?>
    <section class="pg-acct__pets" aria-labelledby="bk-sem-animais">
        <div class="pg-acct__pets-art" aria-hidden="true">
            <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="56" fill="currentColor" opacity=".12"/><g fill="currentColor"><circle cx="34" cy="52" r="9"/><circle cx="50" cy="34" r="9"/><circle cx="70" cy="34" r="9"/><circle cx="86" cy="52" r="9"/><path d="M60 56c-12 0-24 14-24 25 0 7 5 9 10 9 6 0 9-3 14-3s8 3 14 3c5 0 10-2 10-9 0-11-12-25-24-25z"/></g></svg>
        </div>
        <div>
            <h2 id="bk-sem-animais">Primeiro, registe o seu animal</h2>
            <p>Para reservar uma estadia, a equipa precisa de conhecer o animal: raça, vacinas e cuidados especiais.</p>
            <a class="pg-btn pg-btn--primary" href="<?= Url::to(['/pet/create']) ?>">Registar animal</a>
        </div>
    </section>
<?php return; endif; ?>

<?php $form = ActiveForm::begin([
    'id' => 'form-booking',
    'options' => ['class' => 'pg-acct__form pg-bk-form', 'novalidate' => true, 'data-booking-form' => true],
]); ?>

    <div class="pg-acct__group" role="group" aria-labelledby="h-datas">
        <div class="pg-acct__group-head"><h2 id="h-datas">Datas</h2><p>O dia em que deixa os animais e o dia em que os vem buscar.</p></div>
        <div class="pg-acct__fields pg-bk-form__dates">
            <?= $form->field($model, 'datainicio')
                ->input('date', ['min' => $isNew ? $hoje : null, 'data-bk-inicio' => true, 'required' => true])
                ->label('Entrada') ?>
            <?= $form->field($model, 'datafim')
                ->input('date', ['min' => $model->datainicio ?: $hoje, 'data-bk-fim' => true, 'required' => true])
                ->label('Saída') ?>
            <p class="pg-bk-form__nights" data-bk-noites aria-live="polite"></p>
        </div>
    </div>

    <div class="pg-acct__group" role="group" aria-labelledby="h-animais">
        <div class="pg-acct__group-head">
            <h2 id="h-animais">Animais</h2>
            <p>Para cada animal que vem: o quarto, os serviços extra e, se precisar, datas diferentes.</p>
        </div>
        <div class="pg-bk-form__animals">
            <?php foreach ($animais as $animal):
                $id = (int) $animal->id;
                $e = $escolhas[$id] ?? [];
                $escolhido = (string) ($e['quarto'] ?? ($soUmAnimal ? ($quartos[0]->id ?? '') : ''));
                $servEscolhidos = array_map('intval', $e['servicos'] ?? []);
                $proprias = !empty($e['proprias']);
            ?>
                <fieldset class="pg-bk-animal<?= $escolhido !== '' ? ' is-on' : '' ?>" data-bk-animal data-nome="<?= Html::encode($animal->nome) ?>">
                    <legend class="pg-bk-animal__head">
                        <?= $this->render('@frontend/views/pet/_medal', ['nome' => $animal->nome, 'especie' => $animal->getEspecie(), 'foto' => $animal->getFotoUrl(), 'size' => 'sm']) ?>
                        <span>
                            <strong><?= Html::encode($animal->nome) ?></strong>
                            <small><?= Html::encode($animal->getRacaTexto() ?? $animal->getEspecie() ?? 'Raça não indicada') ?></small>
                        </span>
                    </legend>

                    <div class="pg-bk-rooms" role="radiogroup" aria-label="Quarto de <?= Html::encode($animal->nome) ?>">
                        <label class="pg-bk-room pg-bk-room--none">
                            <input type="radio" name="quartos[<?= $id ?>]" value="" data-preco="0"<?= $escolhido === '' ? ' checked' : '' ?>>
                            <span class="pg-bk-room__name">Não vem</span>
                            <span class="pg-bk-room__price">desta vez</span>
                        </label>
                        <?php foreach ($quartos as $q): ?>
                            <label class="pg-bk-room">
                                <input type="radio" name="quartos[<?= $id ?>]" value="<?= $q->id ?>" data-preco="<?= Html::encode((string) $q->preco) ?>" data-quarto="<?= Html::encode(ucfirst($q->categoria)) ?>"<?= $escolhido === (string) $q->id ? ' checked' : '' ?>>
                                <span class="pg-bk-room__name"><?= Html::encode(ucfirst($q->categoria)) ?></span>
                                <span class="pg-bk-room__price"><?= $fmtPreco($q->preco) ?> <small>/ noite</small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php // só aparece quando o animal vem (classe is-on, posta pelo booking.js) ?>
                    <div class="pg-bk-animal__more">
                        <fieldset class="pg-bk-extras">
                            <legend class="pg-bk-extras__title">Serviços extra para <?= Html::encode($animal->nome) ?> <span class="pg-acct__optional">opcional</span></legend>
                            <div class="pg-bk-extras__list">
                                <?php foreach ($servicos as $s): ?>
                                    <label class="pg-bk-chip" title="<?= Html::encode((string) $s->descricao) ?>">
                                        <input type="checkbox" name="servicos[<?= $id ?>][]" value="<?= $s->id ?>" data-preco="<?= Html::encode((string) $s->preco) ?>" data-nome="<?= Html::encode($s->nome) ?>"<?= in_array((int) $s->id, $servEscolhidos, true) ? ' checked' : '' ?>>
                                        <span class="pg-bk-chip__check" aria-hidden="true"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
                                        <span class="pg-bk-chip__name"><?= Html::encode($s->nome) ?></span>
                                        <span class="pg-bk-chip__price"><?= $fmtPreco($s->preco) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <div class="pg-bk-own">
                            <label class="pg-bk-own__toggle">
                                <input type="checkbox" name="datas[<?= $id ?>][proprias]" value="1" data-bk-proprias<?= $proprias ? ' checked' : '' ?>>
                                <span>Datas diferentes para <?= Html::encode($animal->nome) ?></span>
                            </label>
                            <div class="pg-bk-own__dates">
                                <label>
                                    <span>Entrada de <?= Html::encode($animal->nome) ?></span>
                                    <input type="date" class="form-control" name="datas[<?= $id ?>][inicio]" value="<?= Html::encode((string) ($e['inicio'] ?? '')) ?>" data-bk-a-inicio>
                                </label>
                                <label>
                                    <span>Saída de <?= Html::encode($animal->nome) ?></span>
                                    <input type="date" class="form-control" name="datas[<?= $id ?>][fim]" value="<?= Html::encode((string) ($e['fim'] ?? '')) ?>" data-bk-a-fim>
                                </label>
                                <p class="pg-bk-own__hint">Dentro das datas da reserva.</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="pg-acct__savebar pg-bk-form__bar">
        <p class="pg-acct__savebar-text">
            Total estimado: <strong data-bk-total-bar><?= $fmtPreco($model->preco ?? 0) ?></strong>
        </p>
        <div class="pg-acct__savebar-actions">
            <a class="pg-btn pg-btn--outline" href="<?= $isNew ? Url::to(['index']) : Url::to(['view', 'id' => $model->id]) ?>">Voltar</a>
            <?= Html::submitButton($isNew ? 'Enviar pedido de reserva' : 'Guardar alterações', ['class' => 'pg-btn pg-btn--primary']) ?>
        </div>
    </div>

<?php ActiveForm::end(); ?>

<aside class="pg-bk-summary" aria-labelledby="bk-resumo">
    <h2 class="pg-bk-summary__title" id="bk-resumo">Resumo</h2>
    <p class="pg-bk-summary__dates" data-bk-resumo-datas>Escolha as datas.</p>
    <ul class="pg-bk-summary__lines" data-bk-linhas>
        <li class="pg-bk-summary__empty">Escolha o quarto de pelo menos um animal.</li>
    </ul>
    <div class="pg-bk-summary__total">
        <span>Total estimado</span>
        <strong data-bk-total><?= $fmtPreco($model->preco ?? 0) ?></strong>
    </div>
    <p class="pg-bk-summary__note">
        O hotel confirma o pedido e recebe uma notificação com a resposta.
    </p>
</aside>
