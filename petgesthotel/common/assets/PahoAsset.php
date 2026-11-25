<?php

declare(strict_types=1);

namespace common\assets;

use yii\web\AssetBundle;

/**
 * Cliente MQTT para o browser (Eclipse Paho, por WebSockets). Instalado pelo composer (npm-asset/paho-mqtt).
 */
class PahoAsset extends AssetBundle
{
    public $sourcePath = '@npm/paho-mqtt';
    public $js = ['paho-mqtt-min.js'];
    public $publishOptions = ['only' => ['paho-mqtt-min.js']];
}
