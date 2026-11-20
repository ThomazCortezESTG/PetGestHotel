<?php

declare(strict_types=1);

/**
 * Os meus animais (lista do cliente). Segue o estilo da área de cliente (perfil.css):
 * faixa azul no topo, navegação à esquerda e, em cada animal, uma medalha de coleira.
 *
 * @var yii\web\View $this
 * @var frontend\models\PetSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use common\models\Animal;
use frontend\assets\PetAsset;
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

PetAsset::register($this);

$this->title = 'Os meus animais | PetGestHotel';
$this->params['meta_description'] = 'Os animais que pode levar para o PetGestHotel.';

/** @var Animal[] $animais */
$animais = $dataProvider->getModels();
$total = (int) $dataProvider->getTotalCount();
$aPesquisar = trim((string) $searchModel->nome) !== '';

if ($total === 0 && !$aPesquisar) {
    $lead = 'Registe o seu cão ou gato para poder reservar uma estadia.';
} elseif ($total === 1 && !$aPesquisar) {
    $lead = 'Tem 1 animal registado. São estes os dados que a equipa consulta durante a estadia.';
} else {
    $lead = 'Tem ' . $total . ' animais registados. São estes os dados que a equipa consulta durante a estadia.';
}
?>

<div class="pg-acct pg-pets">
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="pets-title">
        <div class="pg-acct__wrap pg-pets__band">
            <div>
                <h1 class="pg-acct__title" id="pets-title">Os meus animais</h1>
                <p class="pg-acct__lead"><?= Html::encode($lead) ?></p>
            </div>
            <?php if ($total > 0 || $aPesquisar): ?>
                <a class="pg-btn pg-btn--primary" href="<?= Url::to(['create']) ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Registar animal
                </a>
            <?php endif; ?>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'animais']) ?>

        <div class="pg-acct__main">
            <?php if ($total > 6 || $aPesquisar): ?>
                <form class="pg-pets__search" method="get" action="<?= Url::to(['index']) ?>" role="search">
                    <label for="pets-q" class="visually-hidden">Procurar animal pelo nome</label>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <input id="pets-q" type="search" name="<?= Html::getInputName($searchModel, 'nome') ?>" value="<?= Html::encode($searchModel->nome) ?>" placeholder="Procurar pelo nome">
                    <?php if ($aPesquisar): ?>
                        <a href="<?= Url::to(['index']) ?>">Limpar</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <?php if ($animais === [] && $aPesquisar): ?>
                <p class="pg-pets__none">Nenhum animal com o nome «<?= Html::encode($searchModel->nome) ?>». Confirme a escrita ou <a href="<?= Url::to(['index']) ?>">veja todos</a>.</p>

            <?php elseif ($animais === []): ?>
                <section class="pg-acct__pets" aria-labelledby="pets-empty">
                    <div class="pg-acct__pets-art" aria-hidden="true">
                        <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="56" fill="currentColor" opacity=".12"/><g fill="currentColor"><circle cx="34" cy="52" r="9"/><circle cx="50" cy="34" r="9"/><circle cx="70" cy="34" r="9"/><circle cx="86" cy="52" r="9"/><path d="M60 56c-12 0-24 14-24 25 0 7 5 9 10 9 6 0 9-3 14-3s8 3 14 3c5 0 10-2 10-9 0-11-12-25-24-25z"/></g></svg>
                    </div>
                    <div>
                        <h2 id="pets-empty">Ainda não tem animais registados</h2>
                        <p>Indique a raça, as vacinas e os cuidados de que precisa. Só depois pode reservar uma estadia.</p>
                        <a class="pg-btn pg-btn--primary" href="<?= Url::to(['create']) ?>">Registar o primeiro animal</a>
                    </div>
                </section>

            <?php else: ?>
                <ul class="pg-pets__list">
                    <?php foreach ($animais as $i => $a):
                        $tipo = $a->especie;
                        $raca = $a->racaTexto;
                        $anos = $a->idadeTexto;
                        $nascido = $a->data ? Yii::$app->formatter->asDate($a->data, 'long') : null;
                        $vacinas = trim((string) $a->vacinas);
                        $obs = trim((string) $a->observacoes);
                    ?>
                        <li class="pg-pet" style="--i: <?= (int) $i ?>">
                            <a class="pg-pet__medal" href="<?= Url::to(['view', 'id' => $a->id]) ?>" tabindex="-1" aria-hidden="true">
                                <?= $this->render('_medal', ['nome' => $a->nome, 'especie' => $tipo, 'foto' => $a->fotoUrl, 'size' => 'sm']) ?>
                            </a>

                            <div class="pg-pet__id">
                                <h2 class="pg-pet__name">
                                    <a href="<?= Url::to(['view', 'id' => $a->id]) ?>"><?= Html::encode($a->nome) ?></a>
                                </h2>
                                <p class="pg-pet__breed">
                                    <?php if ($tipo): ?><span class="pg-pet__kind"><?= Html::encode($tipo) ?></span><?php endif; ?>
                                    <?= $raca !== null ? Html::encode($raca) : '<span class="pg-acct__empty">Raça não indicada</span>' ?>
                                </p>
                                <?php if ($obs !== ''): ?>
                                    <p class="pg-pet__note"><?= Html::encode($obs) ?></p>
                                <?php endif; ?>
                            </div>

                            <dl class="pg-pet__facts">
                                <div>
                                    <dt>Idade</dt>
                                    <dd<?= $nascido ? ' title="Nasceu a ' . Html::encode($nascido) . '"' : '' ?>><?= $anos !== null ? Html::encode($anos) : '<span class="pg-acct__empty">Não indicada</span>' ?></dd>
                                </div>
                                <div>
                                    <dt>Vacinas</dt>
                                    <dd><?php
                                        $nDocs = count($a->documentos);
                                        $boletim = $nDocs === 1 ? 'Boletim anexado' : $nDocs . ' documentos anexados';
                                        if ($vacinas !== '') {
                                            echo Html::encode($vacinas) . ($nDocs > 0 ? ' <span class="pg-pets__aside">(' . $boletim . ')</span>' : '');
                                        } else {
                                            echo $nDocs > 0 ? $boletim : '<span class="pg-pet__warn">Por indicar</span>';
                                        }
                                    ?></dd>
                                </div>
                            </dl>

                            <div class="pg-pet__actions">
                                <a href="<?= Url::to(['update', 'id' => $a->id]) ?>" aria-label="Editar <?= Html::encode($a->nome) ?>">Editar</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($dataProvider->pagination->pageCount > 1): ?>
                    <nav class="pg-pets__pages" aria-label="Páginas">
                        <?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
