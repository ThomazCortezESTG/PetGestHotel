<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        // API REST para a app móvel (ver backend/modules/api/Module.php)
        'api' => [
            'class' => \backend\modules\api\Module::class,
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            // a API recebe JSON no corpo dos pedidos
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // ---- API (app móvel) ----
                'POST api/auth/login' => 'api/auth/login',
                'POST api/auth/logout' => 'api/auth/logout',
                'GET api/auth/me' => 'api/auth/me',
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['api/animais' => 'api/animal'],
                    'tokens' => ['{id}' => '<id:\\d+>', '{doc}' => '<doc:\\d+>'],
                    'extraPatterns' => [
                        'GET,HEAD {id}/documentos' => 'documentos',
                        'GET,HEAD {id}/documentos/{doc}' => 'documento',
                        'GET,HEAD {id}/foto' => 'foto',
                    ],
                ],
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['api/racas' => 'api/raca'],
                    'only' => ['index', 'view', 'options'],
                ],
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['api/conversas' => 'api/conversa'],
                    'only' => ['index', 'view', 'mensagens', 'enviar', 'lida', 'anexo'],
                    'tokens' => ['{id}' => '<id:\\d+>', '{mensagem}' => '<mensagem:\\d+>'],
                    'extraPatterns' => [
                        'GET,HEAD {id}/mensagens' => 'mensagens',
                        'POST {id}/mensagens' => 'enviar',
                        'POST {id}/lida' => 'lida',
                        'GET,HEAD {id}/mensagens/{mensagem}/anexo' => 'anexo',
                    ],
                ],
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['api/notificacoes' => 'api/notificacao'],
                    'only' => ['index', 'lida', 'lidas'],
                    'extraPatterns' => [
                        'POST lidas' => 'lidas',
                        'POST {id}/lida' => 'lida',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
    'as access' => [
        'class' => 'yii\filters\AccessControl',
        // a API tem autenticação própria (token), não exige o papel accessBackend
        'except' => ['api/*'],
        'rules' => [
            [
                'actions' => ['login', 'error'],
                'allow' => true,
            ],
            [
                'allow' => true,
                'roles' => ['accessBackend'],
            ],
        ],
        'denyCallback' => function ($rule, $action) {
            if (Yii::$app->user->isGuest) {
                return Yii::$app->user->loginRequired();
            }

            return Yii::$app->response->redirect(Yii::$app->params['frontendUrl']);
        },
    ],
];
