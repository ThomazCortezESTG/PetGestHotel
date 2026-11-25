<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Booking $model */
/** @var common\models\Quarto[] $quartos */
/** @var common\models\Animal[] $animais */
/** @var common\models\ServicosExtras[] $servicos */

$postQuartos = $escolhasQuartos
        ?? Yii::$app->request->post('quartos', []);

$postServicos = $escolhasServicos
        ?? (array) Yii::$app->request->post('servicos', []);

$listaQuartos = ArrayHelper::map($quartos, 'id', function ($q) {
    return ucfirst($q->categoria) . ' - ' . number_format($q->preco, 2, ',', '.') . ' €/dia';
});
$quartosPorId = ArrayHelper::index($quartos, 'id');
?>

<div class="booking-form">

    <?php if (empty($animais)): ?>

        <div class="alert alert-warning">
            <strong>Ainda não tens animais registados.</strong><br>
            Para fazer uma reserva, primeiro tens de registar pelo menos um animal.
            <div class="mt-2">
                <?= Html::a('Registar animal', ['pet/create'], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>

    <?php else: ?>

        <?php $form = ActiveForm::begin(); ?>

        <?php
        $hoje = date('Y-m-d');

        if (empty($model->datainicio)) {
            $model->datainicio = $hoje;
        }
        ?>

        <?= $form->field($model, 'datainicio')->input('date', [
                'id'  => 'datainicio',
                'min' => $hoje,
        ]) ?>

        <?= $form->field($model, 'datafim')->input('date', [
                'id'  => 'datafim',
                'min' => $model->datainicio ?: $hoje,
        ]) ?>

        <h4>Animais e quartos</h4>

        <?php foreach ($animais as $animal): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <strong><?= Html::encode($animal->nome) ?></strong>

                    <?= Html::radioList(
                            'quartos[' . $animal->id . ']',
                            $postQuartos[$animal->id] ?? null,
                            $listaQuartos,
                            [
                                    'item' => function ($index, $label, $name, $checked, $value) use ($quartosPorId, $animal) {
                                        $preco = $quartosPorId[$value]->preco ?? 0;
                                        $id = 'quarto-' . $animal->id . '-' . $value;
                                        return '<div class="form-check">'
                                                . Html::radio($name, $checked, [
                                                        'value' => $value,
                                                        'id' => $id,
                                                        'class' => 'form-check-input quarto-radio',
                                                        'data-preco' => $preco,
                                                ])
                                                . Html::label($label, $id, ['class' => 'form-check-label'])
                                                . '</div>';
                                    },
                            ]
                    ) ?>
                    <small class="text-muted">Deixa sem selecionar se este animal não vai ficar.</small>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="form-group">
            <label>Serviços extras (aplicados a cada animal)</label>

            <?php foreach ($servicos as $s): ?>
                <div class="form-check">
                    <input type="checkbox"
                           class="form-check-input servico-check"
                           name="servicos[]"
                           value="<?= $s->id ?>"
                           id="servico-<?= $s->id ?>"
                           data-preco="<?= $s->preco ?>"
                            <?= in_array($s->id, $postServicos) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="servico-<?= $s->id ?>">
                        <?= Html::encode($s->nome) ?> - <?= number_format($s->preco, 2, ',', '.') ?> €
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-info">
            <strong>Total a pagar:</strong> <span id="preco-total">0,00</span> €
            <small class="text-muted d-block" id="preco-detalhe"></small>
        </div>

        <div class="form-group">
            <?= Html::submitButton('Reservar', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    <?php endif; ?>

</div>

<script type="text/javascript">
    const inicioEl = document.getElementById('datainicio');
    const fimEl = document.getElementById('datafim');

    function ajustarMinFim() {
        fimEl.min = inicioEl.value;
        if (fimEl.value && fimEl.value < inicioEl.value) {
            fimEl.value = '';
        }
    }

    function atualizarPreco() {
        const total = document.getElementById('preco-total');
        const detalhe = document.getElementById('preco-detalhe');

        const dias = (inicioEl.value && fimEl.value)
            ? Math.round((new Date(fimEl.value) - new Date(inicioEl.value)) / 86400000)
            : 0;

        const quartosMarcados = document.querySelectorAll('.quarto-radio:checked');
        const servicosMarcados = document.querySelectorAll('.servico-check:checked');

        if (dias < 1 || quartosMarcados.length === 0) {
            total.textContent = '0,00';
            detalhe.textContent = (inicioEl.value && fimEl.value && dias < 1)
                ? 'A data de fim tem de ser posterior à de início.'
                : 'Escolhe as datas e um quarto para ver o total.';
            return;
        }

        let somaQuartos = 0;
        quartosMarcados.forEach(r => somaQuartos += parseFloat(r.dataset.preco) || 0);

        let somaServicos = 0;
        servicosMarcados.forEach(c => somaServicos += parseFloat(c.dataset.preco) || 0);

        const valor = somaQuartos * dias + somaServicos * quartosMarcados.length;

        total.textContent = valor.toFixed(2).replace('.', ',');
        detalhe.textContent = quartosMarcados.length + ' animal(is) × ' + dias + ' dia(s)'
            + (servicosMarcados.length
                ? ' + ' + servicosMarcados.length + ' serviço(s) por animal'
                : '');
    }

    inicioEl.addEventListener('change', () => { ajustarMinFim(); atualizarPreco(); });
    fimEl.addEventListener('change', atualizarPreco);
    document.querySelectorAll('.quarto-radio, .servico-check')
        .forEach(el => el.addEventListener('change', atualizarPreco));

    ajustarMinFim();
    atualizarPreco();
</script>