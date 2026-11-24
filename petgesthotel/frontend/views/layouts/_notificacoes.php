<?php

declare(strict_types=1);

/**
 * Sino de notificações do cliente, no topo do site (ver NotificacaoController e web/js/notificacoes.js).
 *
 * @var yii\web\View $this
 */

use common\models\Notificacao;
use frontend\assets\NotificacaoAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

NotificacaoAsset::register($this);

$eu = (int) Yii::$app->user->id;
$naoLidas = Notificacao::naoLidas($eu);
$config = [
    'lista' => Url::to(['/notificacao/lista']),
    'lidas' => Url::to(['/notificacao/lidas']),
    'topico' => Notificacao::topico($eu),
    'wsUrl' => Yii::$app->params['mqtt.wsUrl'] ?? null,
];
?>
<div class="pg-bell" data-notificacoes="<?= Html::encode(Json::encode($config)) ?>">
    <button type="button" class="pg-bell__btn" data-bell-btn aria-expanded="false" aria-controls="pg-bell-panel"
            aria-label="Notificações<?= $naoLidas > 0 ? ', ' . $naoLidas . ' por ler' : '' ?>">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9a6 6 0 1 1 12 0c0 5 2 6.5 2 6.5H4S6 14 6 9z"/><path d="M10 19a2 2 0 0 0 4 0"/></svg>
        <span class="pg-bell__count" data-bell-count<?= $naoLidas > 0 ? '' : ' hidden' ?>><?= $naoLidas > 9 ? '9+' : $naoLidas ?></span>
    </button>

    <div class="pg-bell__panel" id="pg-bell-panel" data-bell-panel hidden>
        <div class="pg-bell__head">
            <h2 class="pg-bell__title">Notificações</h2>
            <button type="button" class="pg-bell__all" data-bell-all hidden>Marcar todas como lidas</button>
        </div>
        <ul class="pg-bell__list" data-bell-list>
            <li class="pg-bell__empty">A carregar…</li>
        </ul>
    </div>
</div>
