<?php

declare(strict_types=1);

/**
 * Avatar do cliente: fotografia ou iniciais, com botão para alterar.
 * Todos os elementos [data-avatar] da página são atualizados em direto depois de carregar/remover (perfil.js).
 *
 * @var common\models\User $user
 * @var common\models\UserProfile $profile
 * @var string $size  xl | lg
 */

use common\models\UserProfile;
use yii\helpers\Html;

$url = $profile->getAvatarUrl();
$iniciais = UserProfile::initialsOf((string) ($profile->nome ?: $user->username));
?>
<div class="pg-avatar pg-avatar--<?= $size ?>">
    <span class="pg-avatar__face" data-avatar data-initials="<?= Html::encode($iniciais) ?>">
        <?php if ($url): ?>
            <img src="<?= Html::encode($url) ?>" alt="">
        <?php else: ?>
            <?= Html::encode($iniciais) ?>
        <?php endif; ?>
    </span>
    <button type="button" class="pg-avatar__edit" data-avatar-pick aria-label="<?= $url ? 'Alterar fotografia de perfil' : 'Adicionar fotografia de perfil' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.6l1.4-2h5l1.4 2h1.6A2.5 2.5 0 0 1 20 8.5v8a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
    </button>
</div>
