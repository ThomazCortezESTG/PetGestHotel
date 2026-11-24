<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Páginas de mensagens do cliente. Reutiliza a base da área de cliente (perfil.css).
 * O componente do chat em si vem do common\assets\ChatAsset.
 */
class ConversaAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/perfil.css',
        'css/conversa.css',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
