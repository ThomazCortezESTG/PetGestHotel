<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';

return [
    'name' => 'PetGestHotel',
    'language' => 'pt-PT',
    'bootstrap' => [
        \common\bootstrap\MailerBootstrap::class,
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'urlManagerBackend' => [
            'class' => 'yii\web\UrlManager',
            'baseUrl' => $params['backendUrl'],
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'urlManagerFrontend' => [
            'class' => 'yii\web\UrlManager',
            'baseUrl' => $params['frontendUrl'],
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        // 'class' explícito: sem ele a consola (php yii) falha, porque lá não existem 'user' nem 'session' por omissão
        'user' => [
            'class' => \yii\web\User::class,
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity', 'httpOnly' => true, 'path' => '/'],
        ],
        'session' => [
            'class' => \yii\web\Session::class,
            'name' => 'app-session',
            'cookieParams' => ['path' => '/'],
        ],
    ],
];
