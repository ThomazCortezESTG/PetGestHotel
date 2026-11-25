<?php

declare(strict_types=1);

namespace common\assets;

use yii\web\AssetBundle;

/**
 * Chat (front-office e back-office): mostra as mensagens, envia-as e recebe os avisos do Mosquitto.
 * Os ficheiros estão em common/assets/chat e são publicados em web/assets de cada aplicação.
 */
class ChatAsset extends AssetBundle
{
    public $sourcePath = '@common/assets/chat';
    public $css = ['chat.css'];
    public $js = ['chat.js'];
    public $depends = [PahoAsset::class];
}
