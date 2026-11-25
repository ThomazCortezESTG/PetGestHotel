<?php

declare(strict_types=1);

/**
 * Componente do chat, igual no front-office e no back-office (o JS está em common/assets/chat).
 *
 * @var yii\web\View $this
 * @var common\models\Conversa $conversa
 * @var array $urls ['mensagens' => ..., 'enviar' => ..., 'lidas' => ...]
 */

use common\assets\ChatAsset;
use common\components\ChatStorage;
use common\components\Mqtt;
use common\components\PetStorage;
use common\models\Mensagem;
use yii\helpers\Html;
use yii\helpers\Json;

ChatAsset::register($this);

$eu = (int) Yii::$app->user->id;
$motivo = $conversa->motivoSemEscrita($eu);
$anexos = $conversa->podeEnviarAnexo($eu);
$request = Yii::$app->request;

$config = [
    'mensagens' => $urls['mensagens'],
    'enviar' => $urls['enviar'],
    'lidas' => $urls['lidas'],
    'topico' => Mqtt::topicoConversa($conversa->id),
    'wsUrl' => Yii::$app->params['mqtt.wsUrl'] ?? null,
    'csrfParam' => $request->csrfParam,
    'csrfToken' => $request->getCsrfToken(),
];
if ($anexos) {
    $config['maxImagem'] = ChatStorage::maxImageBytes();
    $config['maxVideo'] = ChatStorage::maxVideoBytes();
    $config['maxImagemTexto'] = PetStorage::humanSize(ChatStorage::maxImageBytes());
    $config['maxVideoTexto'] = PetStorage::humanSize(ChatStorage::maxVideoBytes());
}
$inputId = 'chat-texto-' . $conversa->id;
?>
<section class="pg-chat" data-chat="<?= Html::encode(Json::encode($config)) ?>" data-conversa-id="<?= (int) $conversa->id ?>" aria-label="Conversa">
    <p class="pg-chat__status" data-chat-status role="status"></p>

    <div class="pg-chat__log" data-chat-log role="log" aria-live="polite" aria-relevant="additions" tabindex="0">
        <p class="pg-chat__empty" data-chat-empty>A carregar mensagens…</p>
    </div>

    <?php if ($motivo === null): ?>
        <p class="pg-chat__error" data-chat-error role="alert" hidden></p>
        <?php if ($anexos): ?>
            <div class="pg-chat__file" data-chat-file hidden>
                <span class="pg-chat__file-thumb" data-chat-file-thumb aria-hidden="true"></span>
                <span class="pg-chat__file-name" data-chat-file-name></span>
                <span class="pg-chat__file-progress" data-chat-file-progress hidden><span></span></span>
                <button type="button" class="pg-chat__file-remove" data-chat-file-remove aria-label="Remover ficheiro">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
        <?php endif; ?>
        <form class="pg-chat__form" data-chat-form>
            <?php if ($anexos): ?>
                <input type="file" data-chat-file-input accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" hidden>
                <button class="pg-chat__attach" type="button" data-chat-attach aria-label="Enviar fotografia ou vídeo" title="Fotografia ou vídeo (até <?= Html::encode($config['maxVideoTexto']) ?>)">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.6"/><path d="M21 16l-5.5-5.5L7 19"/></svg>
                </button>
            <?php endif; ?>
            <label class="visually-hidden sr-only" for="<?= $inputId ?>">Mensagem</label>
            <textarea id="<?= $inputId ?>" rows="1" maxlength="<?= Mensagem::MAX_CHARS ?>" placeholder="Escreva uma mensagem…" autocomplete="off"></textarea>
            <button class="pg-chat__send" type="submit" aria-label="Enviar mensagem">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12l16-8-6 16-2.5-6.5z"/><path d="M11.5 13.5 20 4"/></svg>
            </button>
        </form>
    <?php else: ?>
        <p class="pg-chat__closed"><?= Html::encode($motivo) ?></p>
    <?php endif; ?>
</section>
