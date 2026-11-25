<?php

declare(strict_types=1);

/**
 * Mensagens flash como "toasts": ficam por cima da página (não empurram o conteúdo),
 * entram com animação e fecham sozinhas (os erros ficam até o utilizador os fechar).
 * Comportamento em web/js/petgest.js, estilo em web/css/petgest.css (.pg-toast).
 *
 * @var yii\web\View $this
 */

$session = Yii::$app->session;
if (!$session->getIsActive() && !$session->getHasSessionId()) {
    return;
}

// tipo da flash => [variante visual, segundos até fechar (0 = não fecha sozinho), ícone]
$tipos = [
    'success' => ['success', 5, '<path d="M5 12.5l4.5 4.5L19 7.5"/>'],
    'info'    => ['info', 6, '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6v.1"/>'],
    'warning' => ['warning', 8, '<path d="M12 4l9 16H3z"/><path d="M12 10v4.5M12 17.4v.1"/>'],
    'error'   => ['error', 0, '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>'],
    'danger'  => ['error', 0, '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>'],
];

$toasts = [];
foreach ($session->getAllFlashes(true) as $tipo => $mensagens) {
    if (!isset($tipos[$tipo])) {
        continue;
    }
    foreach ((array) $mensagens as $mensagem) {
        $toasts[] = [$tipos[$tipo], $mensagem];
    }
}
?>
<div class="pg-toasts" aria-live="polite">
    <?php foreach ($toasts as [[$variante, $segundos, $icone], $mensagem]): ?>
        <div class="pg-toast pg-toast--<?= $variante ?>"
             role="<?= $variante === 'error' ? 'alert' : 'status' ?>"
             data-toast
             data-timeout="<?= $segundos ?>"
             style="--toast-time: <?= $segundos ?>s">
            <span class="pg-toast__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><?= $icone ?></svg>
            </span>
            <div class="pg-toast__msg"><?= $mensagem /* as mensagens são escritas no código (podem ter links), tal como no Alert do Yii */ ?></div>
            <button type="button" class="pg-toast__close" data-toast-close aria-label="Fechar mensagem">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <?php if ($segundos > 0): ?>
                <span class="pg-toast__timer" aria-hidden="true"></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
