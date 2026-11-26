<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Reservas do cliente. Reutiliza a base da área de cliente (perfil.css) e as medalhas dos animais (pet.css).
 */
class BookingAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/perfil.css',
        'css/pet.css',
        'css/booking.css',
    ];
    public $js = [
        'js/booking.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
