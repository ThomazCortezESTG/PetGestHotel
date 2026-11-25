<?php

declare(strict_types=1);

/**
 * Formulário do animal (registar e editar), no estilo do formulário do perfil:
 * grupos com título à esquerda, barra de guardar colada ao fundo e a medalha em direto ao lado.
 * Inclui a fotografia e os documentos de vacinas (PDF ou imagem, máximo PetStorage::MAX_DOCS).
 *
 * @var yii\web\View $this
 * @var common\models\Animal $model
 * @var frontend\models\PetFicheirosForm $ficheiros
 */

use common\components\PetStorage;
use common\models\Raca;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

// Raças agrupadas por tipo (Cão, Gato, Outros). O nome do grupo dá a cor da medalha em direto.
$racas = ArrayHelper::map(
    Raca::find()->orderBy(['tipo' => SORT_ASC, 'nome' => SORT_ASC])->all(),
    'id',
    'nome',
    'tipo'
);

$isNew = $model->isNewRecord;
$opcional = ' <span class="pg-acct__optional">opcional</span>';
$maxBytes = PetStorage::maxBytes();
$maxTxt = PetStorage::humanSize($maxBytes);
$docs = $isNew ? [] : $model->documentos;
$removerDocs = $ficheiros->removerDocs;
$fotoUrl = $ficheiros->removerFoto ? null : $model->fotoUrl;
?>

<?php $form = ActiveForm::begin([
    'id' => 'form-pet',
    'options' => [
        'class' => 'pg-acct__form',
        'novalidate' => true,
        'enctype' => 'multipart/form-data',
        'data-max-bytes' => $maxBytes,
        'data-max-post' => min(PetStorage::maxPostBytes(), PHP_INT_MAX),
        'data-max-docs' => PetStorage::MAX_DOCS,
    ],
]); ?>

    <?php if ($erro = Yii::$app->session->getFlash('pet-error')): ?>
        <p class="pg-pets__error" role="alert"><?= Html::encode($erro) ?></p>
    <?php endif; ?>

    <div class="pg-acct__group" id="f-fotografia" role="group" aria-labelledby="h-fotografia">
        <div class="pg-acct__group-head"><h2 id="h-fotografia">Fotografia</h2><p>Ajuda a equipa a reconhecê-lo no dia da entrada.</p></div>
        <div class="pg-acct__fields pg-acct__photo">
            <?= $this->render('_medal', ['nome' => (string) $model->nome, 'especie' => $model->especie, 'foto' => $fotoUrl, 'size' => 'sm']) ?>
            <div class="pg-acct__photo-actions">
                <button type="button" class="pg-btn pg-btn--outline" data-foto-pick>
                    <span data-foto-label><?= $fotoUrl ? 'Alterar fotografia' : 'Carregar fotografia' ?></span>
                </button>
                <?php // recebe a fotografia já recortada (pet.js); sem JS, funciona como um campo de ficheiro normal ?>
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="pg-pets__nojs-file" data-foto-input>
                <button type="button" class="pg-acct__photo-remove" data-foto-remove<?= $fotoUrl ? '' : ' hidden' ?>>Remover</button>
                <input type="hidden" name="remover_foto" value="<?= $ficheiros->removerFoto ? '1' : '' ?>" data-foto-remove-flag>
                <p class="pg-acct__photo-hint">JPG, PNG ou WebP. Pode ajustar o recorte antes de guardar.</p>
                <?php if ($ficheiros->hasErrors('foto')): ?>
                    <p class="pg-pets__field-error" role="alert"><?= Html::encode($ficheiros->getFirstError('foto')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="pg-acct__group" id="f-identificacao" role="group" aria-labelledby="h-identificacao">
        <div class="pg-acct__group-head"><h2 id="h-identificacao">Identificação</h2><p>Como a equipa o vai chamar durante a estadia.</p></div>
        <div class="pg-acct__fields">
            <?= $form->field($model, 'nome')
                ->textInput(['autofocus' => $isNew, 'maxlength' => 100, 'autocomplete' => 'off', 'placeholder' => 'Ex.: Bolinha', 'data-medal-in' => 'nome']) ?>
            <?= $form->field($model, 'data')
                ->input('date', ['max' => date('Y-m-d')])
                ->label('Data de nascimento' . $opcional)
                ->hint('Se não souber o dia certo, indique uma data aproximada.') ?>
        </div>
    </div>

    <div class="pg-acct__group" id="f-raca" role="group" aria-labelledby="h-raca">
        <div class="pg-acct__group-head"><h2 id="h-raca">Raça</h2><p>Ajuda a escolher o alojamento e os cuidados certos.</p></div>
        <div class="pg-acct__fields">
            <?= $form->field($model, 'id_raca_principal')
                ->dropDownList($racas, ['prompt' => 'Escolha a raça', 'required' => true, 'data-medal-in' => 'especie']) ?>
            <?= $form->field($model, 'id_raca_secundaria')
                ->dropDownList($racas, ['prompt' => 'Sem raça secundária'])
                ->label('Raça secundária' . $opcional)
                ->hint('Só para cruzamentos.') ?>
        </div>
    </div>

    <div class="pg-acct__group" id="f-saude" role="group" aria-labelledby="h-saude">
        <div class="pg-acct__group-head"><h2 id="h-saude">Saúde e cuidados</h2><p>A equipa lê isto antes de cada estadia.</p></div>
        <div class="pg-acct__fields">
            <?= $form->field($model, 'vacinas')
                ->textarea(['rows' => 2, 'maxlength' => 255, 'placeholder' => 'Ex.: Raiva e polivalente, março de 2025']) ?>

            <div class="mb-3 pg-docs" data-docs>
                <div class="pg-docs__head">
                    <span class="form-label" id="docs-label">Boletim de vacinas <span class="pg-acct__optional">opcional</span></span>
                    <span class="pg-docs__count" data-docs-count aria-live="polite"></span>
                </div>

                <ul class="pg-docs__list" aria-labelledby="docs-label" data-docs-list>
                    <?php foreach ($docs as $d):
                        $marcado = in_array($d->id, $removerDocs, true); ?>
                        <li class="pg-docs__item<?= $marcado ? ' is-removed' : '' ?>" data-doc-existing>
                            <span class="pg-docs__icon"><?= $this->render('_doc_icon', ['pdf' => $d->isPdf()]) ?></span>
                            <a class="pg-docs__name" href="<?= Url::to(['documento', 'id' => $d->id]) ?>" target="_blank" rel="noopener"><?= Html::encode($d->nome_original) ?></a>
                            <span class="pg-docs__size"><?= PetStorage::humanSize((int) $d->tamanho) ?></span>
                            <label class="pg-docs__remove">
                                <input type="checkbox" name="remover_docs[]" value="<?= $d->id ?>"<?= $marcado ? ' checked' : '' ?> data-doc-remove>
                                <span class="pg-docs__remove-off">Remover</span><span class="pg-docs__remove-on">Anular</span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <template data-docs-icons>
                    <span data-icon="pdf"><?= $this->render('_doc_icon', ['pdf' => true]) ?></span>
                    <span data-icon="img"><?= $this->render('_doc_icon', ['pdf' => false]) ?></span>
                </template>

                <label class="pg-docs__drop" data-docs-drop>
                    <input type="file" name="documentos[]" multiple accept="application/pdf,image/jpeg,image/png,image/webp" data-docs-input>
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    <span><strong>Juntar documentos</strong> ou arraste-os para aqui</span>
                    <small>PDF ou fotografia do boletim. Até <?= PetStorage::MAX_DOCS ?> ficheiros, <?= $maxTxt ?> cada.</small>
                </label>
                <p class="pg-pets__field-error" role="alert" data-docs-error<?= $ficheiros->hasErrors('documentos') ? '' : ' hidden' ?>><?= Html::encode(implode(' ', $ficheiros->getErrors('documentos'))) ?></p>
            </div>

            <?= $form->field($model, 'observacoes')
                ->textarea(['rows' => 3, 'maxlength' => 255, 'placeholder' => 'Medicação, alimentação, medos, alergias…'])
                ->label('Observações' . $opcional) ?>
        </div>
    </div>

    <div class="pg-acct__savebar" data-savebar>
        <p class="pg-acct__savebar-text" aria-live="polite" data-savebar-text><?= $isNew ? 'Pode alterar estes dados mais tarde.' : 'Sem alterações.' ?></p>
        <div class="pg-acct__savebar-actions">
            <a class="pg-btn pg-btn--outline" href="<?= $isNew ? Url::to(['index']) : Url::to(['view', 'id' => $model->id]) ?>">Cancelar</a>
            <?= Html::submitButton($isNew ? 'Registar animal' : 'Guardar alterações', ['class' => 'pg-btn pg-btn--primary']) ?>
        </div>
    </div>

<?php ActiveForm::end(); ?>

<?= $this->render('@frontend/views/perfil/_cropper', ['para' => 'animal']) ?>

<aside class="pg-acct__preview pg-pets__preview" aria-label="Pré-visualização">
    <p class="pg-acct__preview-label">Assim fica a medalha</p>
    <?= $this->render('_medal', ['nome' => (string) $model->nome, 'especie' => $model->especie, 'foto' => $fotoUrl, 'size' => 'lg']) ?>
</aside>
