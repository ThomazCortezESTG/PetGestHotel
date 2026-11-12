<?php

declare(strict_types=1);

Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');

// Descobrir o URL base automaticamente (só em pedidos web)
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_NAME'])) {
    $webDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    // tira o "/frontend/web" ou "/backend/web" do fim
    $root = preg_replace('#/(frontend|backend)/web$#', '', $webDir);

    Yii::setAlias('@frontendUrl', $root . '/frontend/web');
    Yii::setAlias('@backendUrl', $root . '/backend/web');
}