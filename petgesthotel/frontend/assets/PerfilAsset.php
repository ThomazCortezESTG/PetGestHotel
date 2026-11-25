<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Estilos e comportamento da área de cliente (perfil).
 */
class PerfilAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/perfil.css',
    ];
    public $js = [
        'js/pendulum.js',
        'js/cropper.js',
        'js/perfil.js',
        'js/avatar.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
