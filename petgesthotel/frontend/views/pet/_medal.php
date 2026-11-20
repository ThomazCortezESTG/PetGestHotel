<?php

declare(strict_types=1);

/**
 * Medalha de coleira do animal. Com fotografia, a foto preenche a medalha; sem ela, mostra a inicial.
 * A cor (do aro, com foto) diz a espécie: cão laranja, gato teal, outros azul.
 * No formulário, data-medal-out="..." e a fotografia são atualizados em direto (ver pet.js).
 *
 * @var string $nome
 * @var string|null $especie
 * @var string|null $foto  URL da fotografia
 * @var string $size  'sm' (lista, formulário), 'lg' (ficha e pré-visualização)
 */

use yii\helpers\Html;

$size ??= 'sm';
$foto ??= null;
$tom = match ($especie ?? null) {
    'Gato' => 'gato',
    'Outros' => 'outro',
    default => 'cao',
};
$inicial = mb_strtoupper(mb_substr(trim($nome), 0, 1));
?>
<span class="pg-medal pg-medal--<?= $size ?> pg-medal--<?= $tom ?><?= $foto ? ' has-photo' : '' ?>" aria-hidden="true" data-medal>
    <span class="pg-medal__ring"></span>
    <span class="pg-medal__disc">
        <img class="pg-medal__photo" src="<?= $foto ? Html::encode($foto) : '' ?>" alt="" data-medal-photo<?= $foto ? '' : ' hidden' ?>>
        <span class="pg-medal__initial" data-medal-out="inicial"><?= Html::encode($inicial !== '' ? $inicial : '?') ?></span>
        <?php if ($size === 'lg'): ?>
            <span class="pg-medal__name" data-medal-out="nome"><?= Html::encode(trim($nome) !== '' ? $nome : 'Nome') ?></span>
        <?php endif; ?>
    </span>
</span>
