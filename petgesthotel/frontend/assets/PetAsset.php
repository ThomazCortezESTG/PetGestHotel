<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Estilos e comportamento das páginas dos animais do cliente. Reutiliza a base da área de cliente (perfil.css).
 */
class PetAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/perfil.css',
        'css/pet.css',
    ];
    public $js = [
        'js/pendulum.js',
        'js/cropper.js',
        'js/pet.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
