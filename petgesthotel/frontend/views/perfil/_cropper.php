<?php

declare(strict_types=1);

/**
 * Janela de recorte de fotografias (uma por página). Comportamento em cropper.js.
 * - Perfil (por defeito): avatar.js envia logo a foto recortada para perfil/avatar.
 * - Animal ($para = 'animal'): pet.js mete a foto recortada no formulário, que é gravada com o resto.
 *
 * @var yii\web\View $this
 * @var string|null $para 'perfil' (por defeito) ou 'animal'
 */

use yii\helpers\Url;

$para ??= 'perfil';
$animal = $para === 'animal';
?>
<input type="file" accept="image/jpeg,image/png,image/webp" hidden data-crop-input>

<dialog class="pg-crop" data-cropper
    <?php if (!$animal): ?>
        data-upload-url="<?= Url::to(['/perfil/avatar']) ?>"
        data-remove-url="<?= Url::to(['/perfil/avatar-remove']) ?>"
    <?php endif; ?>
        aria-labelledby="crop-title">
    <div class="pg-crop__panel">
        <header class="pg-crop__head">
            <h2 id="crop-title"><?= $animal ? 'Ajustar fotografia do animal' : 'Ajustar fotografia' ?></h2>
            <button type="button" class="pg-crop__x" data-crop-cancel aria-label="Fechar">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </header>

        <div class="pg-crop__stage" data-crop-stage tabindex="0" aria-label="Área de recorte. Arraste ou use as setas para mover; + e − para aproximar.">
            <img class="pg-crop__img" data-crop-img alt="" draggable="false">
            <div class="pg-crop__mask" aria-hidden="true"></div>
            <div class="pg-crop__grid" aria-hidden="true"></div>
        </div>
        <p class="pg-crop__hint">Arraste para posicionar. Use a roda do rato ou dois dedos para aproximar.</p>

        <div class="pg-crop__zoom">
            <button type="button" data-crop-zoom="-1" aria-label="Afastar">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4M8 11h6"/></svg>
            </button>
            <input type="range" min="1" max="4" step="0.01" value="1" data-crop-range aria-label="Zoom">
            <button type="button" data-crop-zoom="1" aria-label="Aproximar">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4M8 11h6M11 8v6"/></svg>
            </button>
        </div>

        <footer class="pg-crop__actions">
            <button type="button" class="pg-btn pg-btn--outline" data-crop-other>Escolher outra</button>
            <button type="button" class="pg-btn pg-btn--primary" data-crop-save><?= $animal ? 'Usar fotografia' : 'Guardar fotografia' ?></button>
        </footer>
    </div>
</dialog>
