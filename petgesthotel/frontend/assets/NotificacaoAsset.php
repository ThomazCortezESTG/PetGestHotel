<?php

declare(strict_types=1);

namespace frontend\assets;

use common\assets\PahoAsset;
use yii\web\AssetBundle;

/**
 * Sino de notificações do cliente (topo do site).
 */
class NotificacaoAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['css/notificacoes.css'];
    public $js = ['js/notificacoes.js'];
    public $depends = [
        AppAsset::class,
        PahoAsset::class,
    ];
}
