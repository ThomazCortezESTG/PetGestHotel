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
    'frontendUrl' => Yii::getAlias('@frontendUrl', false) ?: '/frontend/web'
];