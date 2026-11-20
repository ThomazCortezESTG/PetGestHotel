<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'geral@petgesthotel.pt',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'backendUrl' => Yii::getAlias('@backendUrl', false) ?: '/backend/web',
    'frontendUrl' => Yii::getAlias('@frontendUrl', false) ?: '/frontend/web',

    // Pasta onde ficam as fotografias de perfil (alias do Yii ou caminho absoluto).
    // No servidor, mudar em common/config/params-local.php para uma pasta fora do repositório,
    // com escrita para o utilizador do servidor web. Ex.: '/var/www/petgest-data/avatars'
    'avatar.path' => '@frontend/web/uploads/avatars',
];