<?php

declare(strict_types=1);

/**
 * Navegação da área de cliente. As secções que ainda não existem aparecem como "Em breve".
 *
 * @var string $active
 */

use yii\helpers\Url;

$icon = static fn(string $d): string =>
    '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';

$items = [
    ['perfil', 'O meu perfil', Url::to(['/perfil/index']), $icon('<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>')],
    ['animais', 'Os meus animais', Url::to(['/pet/index']), $icon('<circle cx="5.5" cy="10" r="2"/><circle cx="9.5" cy="5.8" r="2"/><circle cx="14.5" cy="5.8" r="2"/><circle cx="18.5" cy="10" r="2"/><path d="M12 11.5c-3 0-6 3.6-6 6.2 0 1.7 1.3 2.3 2.6 2.3 1.4 0 2.2-.8 3.4-.8s2 .8 3.4.8c1.3 0 2.6-.6 2.6-2.3 0-2.6-3-6.2-6-6.2z"/>')],
    ['reservas', 'Reservas', null, $icon('<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>')],
    ['faturas', 'Faturas', null, $icon('<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>')],
];
?>
<nav class="pg-acct__nav" aria-label="Área de cliente">
    <ul>
        <?php foreach ($items as [$key, $label, $url, $svg]): ?>
            <li>
                <?php if ($url !== null): ?>
                    <a href="<?= $url ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= $svg ?><span><?= $label ?></span></a>
                <?php else: ?>
                    <span class="pg-acct__nav-off"><?= $svg ?><span><?= $label ?></span><small>Em breve</small></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
