<?php

declare(strict_types=1);

/**
 * Ficha de um animal, no estilo do perfil: faixa azul com a medalha pendurada e os dados em grupos.
 *
 * @var yii\web\View $this
 * @var common\models\Animal $model
 */

use frontend\assets\PetAsset;
use yii\helpers\Html;
use yii\helpers\Url;

PetAsset::register($this);

$this->title = $model->nome . ' | PetGestHotel';

$valor = static fn(?string $v, string $vazio = 'Não indicado'): string =>
    ($v === null || trim($v) === '') ? '<span class="pg-acct__empty">' . $vazio . '</span>' : Html::encode($v);

$especie = $model->especie;
$idade = $model->idadeTexto;
$nascido = $model->data ? Yii::$app->formatter->asDate($model->data, 'long') : null;

// Frase de apresentação: "Labrador Retriever cruzado com Caniche (Poodle), 5 anos."
$partes = array_filter([$model->racaTexto, $idade]);
$lead = $partes ? implode(', ', $partes) . '.' : 'Ainda faltam a raça e a idade.';

// O que falta para a primeira estadia correr bem
$emFalta = [];
$semVacinas = trim((string) $model->vacinas) === '' && $model->documentos === [];
if ($semVacinas) {
    $emFalta[] = 'vacinas';
}
if ($model->data === null) {
    $emFalta[] = 'data de nascimento';
}

// Documentos de vacinas: abrem num separador novo (servidos pelo PetController, só para o dono)
$docsHtml = '<span class="pg-acct__empty">Nenhum documento</span>';
if ($model->documentos !== []) {
    $docsHtml = '<ul class="pg-docs__list pg-docs__list--view">';
    foreach ($model->documentos as $d) {
        $docsHtml .= '<li class="pg-docs__item"><span class="pg-docs__icon">' . $this->render('_doc_icon', ['pdf' => $d->isPdf()]) . '</span>'
            . Html::a(Html::encode($d->nome_original), ['documento', 'id' => $d->id], ['class' => 'pg-docs__name', 'target' => '_blank', 'rel' => 'noopener'])
            . '<span class="pg-docs__size">' . \common\components\PetStorage::humanSize((int) $d->tamanho) . '</span></li>';
    }
    $docsHtml .= '</ul>';
}

$grupos = [
    'identificacao' => [
        'titulo' => 'Identificação',
        'texto' => 'Como a equipa o vai chamar durante a estadia.',
        'linhas' => [
            'Nome' => Html::encode($model->nome),
            'Data de nascimento' => $nascido ? Html::encode($nascido) . ($idade ? ' <span class="pg-pets__aside">(' . Html::encode($idade) . ')</span>' : '') : $valor(null, 'Não indicada'),
        ],
    ],
    'raca' => [
        'titulo' => 'Raça',
        'texto' => 'Ajuda a escolher o alojamento e os cuidados certos.',
        'linhas' => [
            'Espécie' => $valor($especie, 'Não indicada'),
            'Raça principal' => $valor($model->racaPrincipal?->nome, 'Não indicada'),
            'Raça secundária' => $valor($model->racaSecundaria?->nome, 'Nenhuma'),
        ],
    ],
    'saude' => [
        'titulo' => 'Saúde e cuidados',
        'texto' => 'A equipa lê isto antes de cada estadia.',
        'linhas' => [
            'Vacinas' => $valor($model->vacinas, 'Por indicar'),
            'Boletim de vacinas' => $docsHtml,
            'Observações' => $valor($model->observacoes, 'Sem observações'),
        ],
    ],
];
?>

<div class="pg-acct pg-pets" data-pet-view>
    <section class="pg-acct__band" aria-labelledby="pet-title">
        <div class="pg-acct__wrap pg-acct__band-inner">
            <div class="pg-acct__hello">
                <a class="pg-acct__back" href="<?= Url::to(['index']) ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    Os meus animais
                </a>
                <h1 class="pg-acct__title" id="pet-title"><?= Html::encode($model->nome) ?></h1>
                <p class="pg-acct__lead"><?= Html::encode($lead) ?></p>
                <a class="pg-btn pg-btn--primary pg-acct__cta" href="<?= Url::to(['update', 'id' => $model->id]) ?>">Editar dados</a>
            </div>
            <div class="pg-pets__hang">
                <?= $this->render('_medal', ['nome' => $model->nome, 'especie' => $especie, 'foto' => $model->fotoUrl, 'size' => 'lg']) ?>
            </div>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'animais']) ?>

        <div class="pg-acct__main">
            <?php if ($erro = Yii::$app->session->getFlash('pet-error')): ?>
                <p class="pg-pets__error" role="alert"><?= Html::encode($erro) ?></p>
            <?php endif; ?>

            <?php if ($emFalta !== []): ?>
                <div class="pg-acct__meter pg-pets__missing">
                    <div class="pg-acct__meter-text">
                        <p><strong>Falta preencher:</strong> <?= Html::encode(implode(' e ', $emFalta)) ?>. Precisamos destes dados antes da primeira estadia.</p>
                        <a href="<?= Url::to(['update', 'id' => $model->id, '#' => $semVacinas ? 'f-saude' : 'f-identificacao']) ?>">Completar</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($grupos as $id => $g): ?>
                <section class="pg-acct__group" aria-labelledby="g-<?= $id ?>">
                    <div class="pg-acct__group-head">
                        <h2 id="g-<?= $id ?>"><?= $g['titulo'] ?></h2>
                        <p><?= $g['texto'] ?></p>
                    </div>
                    <dl class="pg-acct__rows">
                        <?php foreach ($g['linhas'] as $rotulo => $html): ?>
                            <div><dt><?= $rotulo ?></dt><dd><?= $html ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                    <a class="pg-acct__group-edit" href="<?= Url::to(['update', 'id' => $model->id, '#' => 'f-' . $id]) ?>" aria-label="Editar <?= mb_strtolower($g['titulo']) ?>">Editar</a>
                </section>
            <?php endforeach; ?>

            <section class="pg-pets__danger" aria-labelledby="g-remover">
                <div>
                    <h2 id="g-remover">Remover <?= Html::encode($model->nome) ?></h2>
                    <p>Apaga a ficha deste animal da sua conta. Não é possível desfazer.</p>
                </div>
                <?= Html::a('Remover animal', ['delete', 'id' => $model->id], [
                    'class' => 'pg-btn pg-pets__remove',
                    'data' => [
                        'confirm' => 'Remover ' . $model->nome . ' da sua conta? Não é possível desfazer.',
                        'method' => 'post',
                    ],
                ]) ?>
            </section>
        </div>
    </div>
</div>
