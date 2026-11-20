<?php

declare(strict_types=1);

/**
 * Registar um animal.
 *
 * @var yii\web\View $this
 * @var common\models\Animal $model
 * @var frontend\models\PetFicheirosForm $ficheiros
 */

use frontend\assets\PetAsset;
use yii\helpers\Url;

PetAsset::register($this);

$this->title = 'Registar animal | PetGestHotel';
$this->params['meta_description'] = 'Registe o seu cão ou gato no PetGestHotel.';
?>

<div class="pg-acct pg-acct--edit pg-pets">
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="pet-title">
        <div class="pg-acct__wrap">
            <a class="pg-acct__back" href="<?= Url::to(['index']) ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                Os meus animais
            </a>
            <h1 class="pg-acct__title" id="pet-title">Registar animal</h1>
            <p class="pg-acct__lead">Só o nome e a raça são obrigatórios. O resto pode completar antes da primeira estadia.</p>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('@frontend/views/perfil/_nav', ['active' => 'animais']) ?>
        <div class="pg-acct__main">
            <?= $this->render('_form', ['model' => $model, 'ficheiros' => $ficheiros]) ?>
        </div>
    </div>
</div>
