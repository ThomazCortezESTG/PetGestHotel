<?php

declare(strict_types=1);

/**
 * Fotografia (ou iniciais) do colaborador responsável. Sem responsável, mostra o logótipo da equipa.
 *
 * @var common\models\Conversa $conversa
 * @var string $class
 */

use common\models\Conversa;
use yii\helpers\Html;

$p = $conversa->colaborador ? Conversa::pessoa($conversa->colaborador) : null;
?>
<span class="<?= $class ?>" aria-hidden="true">
    <?php if ($p === null): ?>
        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><circle cx="5.5" cy="10" r="2"/><circle cx="9.5" cy="5.8" r="2"/><circle cx="14.5" cy="5.8" r="2"/><circle cx="18.5" cy="10" r="2"/><path d="M12 11.5c-3 0-6 3.6-6 6.2 0 1.7 1.3 2.3 2.6 2.3 1.4 0 2.2-.8 3.4-.8s2 .8 3.4.8c1.3 0 2.6-.6 2.6-2.3 0-2.6-3-6.2-6-6.2z"/></svg>
    <?php elseif ($p['foto_url']): ?>
        <?= Html::img($p['foto_url'], ['alt' => '']) ?>
    <?php else: ?>
        <?= Html::encode($p['iniciais']) ?>
    <?php endif; ?>
</span>
