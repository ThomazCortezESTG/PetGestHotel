<?php

declare(strict_types=1);

/**
 * Etiqueta de hóspede (estilo etiqueta de bagagem de hotel) com os dados do cliente.
 * No formulário, os campos com data-tag="..." atualizam-na em direto (ver perfil.js).
 *
 * @var common\models\User $user
 * @var common\models\UserProfile $profile
 */

use yii\helpers\Html;

$nome = trim((string) $profile->nome);
$tel = $profile->telefone ? trim(chunk_split($profile->telefone, 3, ' ')) : '';
$numero = str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
?>
<div class="pg-tag" aria-hidden="true">
    <svg class="pg-tag__string" viewBox="0 0 60 90" preserveAspectRatio="none">
        <path d="M30 0 C 8 30, 52 50, 30 90" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
    </svg>
    <div class="pg-tag__body">
        <span class="pg-tag__hole"></span>
        <div class="pg-tag__top">
            <span class="pg-tag__brand">PetGestHotel</span>
            <span class="pg-tag__no">Nº <?= $numero ?></span>
        </div>
        <p class="pg-tag__label">Responsável</p>
        <p class="pg-tag__name" data-tag-out="nome" data-empty="O seu nome"><?= $nome !== '' ? Html::encode($nome) : 'O seu nome' ?></p>
        <dl class="pg-tag__rows">
            <div><dt>Telefone</dt><dd data-tag-out="telefone" data-empty="—"><?= $tel !== '' ? Html::encode($tel) : '—' ?></dd></div>
            <div><dt>NIF</dt><dd data-tag-out="n_contribuinte" data-empty="—"><?= $profile->n_contribuinte ? Html::encode($profile->n_contribuinte) : '—' ?></dd></div>
        </dl>
        <div class="pg-tag__foot">
            <span>Leiria</span>
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="5.8" r="2.2"/><circle cx="14.5" cy="5.8" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/><path d="M12 11.5c-3 0-6 3.6-6 6.2 0 1.7 1.3 2.3 2.6 2.3 1.4 0 2.2-.8 3.4-.8s2 .8 3.4.8c1.3 0 2.6-.6 2.6-2.3 0-2.6-3-6.2-6-6.2z"/></svg>
        </div>
    </div>
</div>
