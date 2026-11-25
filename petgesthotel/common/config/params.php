<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'geral@petgesthotel.pt',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'PetGestHotel',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'backendUrl' => Yii::getAlias('@backendUrl', false) ?: '/backend/web',
    'frontendUrl' => Yii::getAlias('@frontendUrl', false) ?: '/frontend/web',

    // Pasta onde ficam as fotografias de perfil (alias do Yii ou caminho absoluto).
    // No servidor, mudar em common/config/params-local.php para uma pasta fora do repositório,
    // com escrita para o utilizador do servidor web. Ex.: '/var/www/petgest-data/avatars'
    'avatar.path' => '@frontend/web/uploads/avatars',

    // Pasta das fotografias e documentos de vacinas dos animais. Fica fora da pasta pública (web/):
    // os ficheiros só saem pelo PetController, que confirma que o animal é de quem pede.
    'animal.path' => '@frontend/uploads/animais',

    // Fotografias e vídeos enviados no chat. Fora da pasta pública, como os dos animais.
    'chat.path' => '@frontend/uploads/chat',

    // Mosquitto (chat em tempo real). Ver mosquitto/README.md.
    // O PHP publica por TCP (host/port); os browsers subscrevem por WebSockets (wsUrl);
    // a app Android liga-se por TCP ao mesmo host, porta 1883.
    // No servidor de produção, mudar em common/config/params-local.php.
    'mqtt.enabled' => true,
    'mqtt.host' => '127.0.0.1',
    'mqtt.port' => 1883,
    'mqtt.username' => null,
    'mqtt.password' => null,
    // null = mesmo host da página, porta 9001 (ws:// ou wss:// conforme a página)
    'mqtt.wsUrl' => null,
    'mqtt.topicPrefix' => 'petgest',
];